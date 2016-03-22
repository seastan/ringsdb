(function app_suggestions(suggestions, $) {

    // Heuristics
    suggestions.heroesInDeck = [];
    suggestions.cardsInDeck = [];
    suggestions.cards = [];
    suggestions.spheresInDeck = [];
    suggestions.targetsInDeck = [];
    suggestions.traitSpecificTargetsInDeck = [];
    suggestions.targetOptionsForDeck = [];
    suggestions.traitSpecificTargetOptionsForDeck = [];
    suggestions.cardNamesInDeck = {};

    // Statistics
    suggestions.codesFromindex = [];
    suggestions.matrix = [];
    suggestions.indexFromCodes = {};


    suggestions.current = [];
    suggestions.list = [];
    suggestions.exclusions = [];
    suggestions.number = 3;
    suggestions.isLoaded = false;
    suggestions.initialized = false;

    suggestions.traits = ['dwarf', 'rohan', 'silvan', 'noldor', 'gondor', 'ent', 'eagle', 'dunedain', 'hobbit', 'istari', 'outlands', 'ranger', 'scout', 'outlands', 'ranged', 'sentinel', 'weapon', 'noble', 'warrior'];

    var inStr = function(hay, needle, ignoreCase) {
        var text = (hay || '');

        if (ignoreCase) {
            text = text.toLowerCase();
        }
        return text.indexOf(needle) != -1;
    };

    var isCard = function(card, name, pack) {
        return card.s_name == name && card.pack_code == pack;
    };

    /**
     * @memberOf suggestions
     */
    suggestions.query = function query() {
        suggestions.promise = $.ajax('/suggestions.json', {
            dataType: 'json',
            success: function(data) {
                suggestions.codesFromindex = data.index;
                suggestions.matrix = data.matrix;

                // reconstitute the full matrix from the lower half matrix
                for (var i = 0; i < suggestions.matrix.length; i++) {
                    for (var j = i; j < suggestions.matrix.length; j++) {
                        suggestions.matrix[i][j] = suggestions.matrix[j][i];
                    }
                }

                for (var i = 0; i < suggestions.codesFromindex.length; i++) {
                    suggestions.indexFromCodes[suggestions.codesFromindex[i]] = i;
                }

                suggestions.isLoaded = true;
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
            }
        });
        suggestions.promise.done(suggestions.compute);
    };

    /**
     * @memberOf suggestions
     */
    suggestions.compute = function compute() {
        if (suggestions.number) {
            suggestions.cardsInDeck = app.deck.get_cards();

            if (suggestions.cardsInDeck.length == 0) {
                return;
            }

            // init current suggestions
            suggestions.codesFromindex.forEach(function(code, index) {
                suggestions.current[index] = {
                    code: code,
                    proba: 0
                };
            });

            // find used cards
            var indexes = _.pluck(app.data.cards.find({ indeck: { '$gt': 0 } }), 'code').map(function(code) {
                return suggestions.indexFromCodes[code];
            });

            // add suggestions of all used cards
            indexes.forEach(function(i) {
                if (suggestions.matrix[i]) {
                    suggestions.matrix[i].forEach(function(value, j) {
                        suggestions.current[j].proba += (value || 0);
                    });
                }
            });

            // remove suggestions of already used cards
            indexes.forEach(function(i) {
                if (suggestions.current[i]) {
                    suggestions.current[i].proba = 0;
                }
            });

            // remove suggestions of heroes
            _.pluck(app.data.cards.find({ type_code: 'hero' }), 'code').map(function(code) {
                return suggestions.indexFromCodes[code];
            }).forEach(function(i) {
                if (suggestions.current[i]) {
                    suggestions.current[i].proba = 0;
                }
            });

            // remove suggestions of excluded cards
            suggestions.exclusions.forEach(function(i) {
                suggestions.current[i].proba = 0;
            });

            // sort suggestions
            suggestions.current.sort(function(a, b) {
                if (b.proba == a.proba) {
                    return a.code > b.code ? 1 : -1;
                }

                return (b.proba - a.proba);
            });

            // Heuristics

            suggestions.heroesInDeck = app.deck.get_hero_deck();
            suggestions.cards = app.data.cards.find();

            suggestions.spheresInDeck = suggestions.getAvailableSpheres(suggestions.heroesInDeck);

            suggestions.targetsInDeck = suggestions.getTargetsInDeck(suggestions.cardsInDeck);
            suggestions.traitSpecificTargetsInDeck = suggestions.getTraitSpecificTargetsInDeck(suggestions.targetsInDeck);

            suggestions.targetOptionsForDeck = suggestions.getTargetOptionsForDeck(suggestions.cardsInDeck);
            suggestions.traitSpecificTargetOptionsForDeck = suggestions.getTraitSpecificTargetOptionsForDeck(suggestions.targetOptionsForDeck);

            suggestions.cardNamesInDeck = _.pluck(suggestions.cardsInDeck, 's_name');

            suggestions.list = _.filter(suggestions.current, function(card) {
                var card = app.data.cards.findById(card.code);

                return suggestions.check(card);
            });

        }
        suggestions.show();
    };

    suggestions.check = function(card) {
        // There is a card with the same name in the deck
        if (_.contains(suggestions.cardNamesInDeck, card.s_name)) {
            return false;
        }

        // Check if there is a hero with access to the necessary sphere to play the card
        if (!suggestions.sphereAccess(card) && card.type_code != 'hero') {
            return false;
        }

        // Never suggest cards of these spheres
        if (card.sphere_code == 'baggins' || card.sphere == 'fellowship') {
            return false;
        }

        // Check if there is a proper target in the deck that can use the card
        if (!suggestions.deckHasTargetOption(card)) {
            return false;
        }

        // No not suggest more heroes if there are 3 heroes in the deck
        if (suggestions.heroesInDeck.length >= 3 && card.type_code == 'hero') {
            return false;
        }

        return true;
    };

    suggestions.matchInTargetLists = function(listA, listB) {
        return _.find(listA, function(el) {
            return _.find(listB, el);
        });
    };

    suggestions.sphereAccess = function(card) {
        var validSphere = _.contains(suggestions.spheresInDeck, card.sphere_code);

        if (validSphere) {
            return validSphere;
        }

        var isAlly = card.type_code == 'ally';
        var isOutlands = inStr(card.traits, 'Outlands');

        _.each(suggestions.heroesInDeck, function(cardInDeck) {
            // Test if deck has access to given sphere
            if (!validSphere && isAlly && isCard(cardInDeck, 'elrond', 'SaF')) {
                validSphere = true;
            }

            if (!validSphere && isAlly && isCard(cardInDeck, 'hirluin the fair', 'SaF') && isOutlands) {
                validSphere = true;
            }
        });

        return validSphere;
    };

    // Returns true if the deck has an eligible target for the card
    suggestions.deckHasTargetOption = function(card) {
        var targetsInCard = suggestions.getTargetsInCard(card);

        // Just have to macth one of the targets in the card with a targetoption for the deck
        if (targetsInCard.length == 0) {
            return true;
        }

        return _.find(targetsInCard, function(target) {
            if (suggestions.isTargetInList(target, suggestions.targetOptionsForDeck)) {
                return true;
            }
        });
    };

    // Checks if a target is in a list of targets
    suggestions.isTargetInList = function(target, list) {
        return _.find(list, target);
    };

    suggestions.getAvailableSpheres = function(heroes) {
        var spheres = [];

        _.each(heroes, function(hero) {
            spheres.push(hero.sphere_code);

            if (hero.s_name == 'oin' || hero.s_name == 'amarthiul') {
                spheres.push('tactics');
            }
        });

        if (spheres.length) {
            spheres.push('neutral');
        }

        return _.unique(spheres);
    };

    // Gets a list of targets in the deck
    suggestions.getTargetsInDeck = function(cards) {
        var targets = [];

        _.each(cards, function(card) {
            [].push.apply(targets, suggestions.getTargetsInCard(card));
        });

        return _.unique(targets, function(item) {
            return item.trait + ':' + item.type;
        });
    };

    // Gets a list of targets in the deck that specify one of the globally named traits
    suggestions.getTraitSpecificTargetsInDeck = function(targetsInDeck) {
        return _.filter(targetsInDeck, function(el) {
            return _.contains(suggestions.traits, el.trait);
        });
    };

    suggestions.getTraitSpecificTargetsInCard = function(card) {
        return _.filter(suggestions.getTargetsInCard(card), function(el) {
            return _.contains(suggestions.traits, el.trait);
        });
    };

    suggestions.getTargetsInCard = function(card) {
        if (card.targets) {
            return card.targets;
        } else {
            var targets = suggestions.getTargetsInCardWithoutCache(card);
            app.data.cards.updateById(card.code, {
                targets: targets
            });
            return targets;
        }
    };

    suggestions.getTargetsInCardWithoutCache = function(card) {

        var targets = []; // List of targets named by the card.
        // A returned list for Quick Ears would look like [{trait:'Dunedain',type:'hero'},{trait:'Ranger',type:'hero'}]
        // A returned list for Unexpected Courage would look like [{trait:'None',type:'hero'}]
        // A returned list for Spear of the Citadel would look like [{trait:'Tactics',type:'character'}]
        // A returned list for Valiant Sacrifice would be [{trait:none,type:'ally'}]

        // Special cases
        if (card.s_name == 'radagast' && card.pack_code == 'JtR') {
            targets.push({ trait: 'creature', type: 'hero' });
            targets.push({ trait: 'creature', type: 'ally' });
            targets.push({ trait: 'creature', type: 'character' });
            return targets;
        }

        if (card.s_name == 'nori' && card.pack_code == 'OHaUH') {
            targets.push({ trait: 'dwarf', type: 'ally' });
            return targets;
        }

        if (card.s_name == 'boromir' && card.pack_code == 'HoN') {
            targets.push({ trait: 'gondor', type: 'ally' });
            return targets;
        }

        if (card.s_name == 'hirluin the fair' && card.pack_code == 'TSF') {
            targets.push({ trait: 'outlands', type: 'ally' });
            return targets;
        }

        // Match certain attachments
        var atraitattachment = /([A-Z][\w]+) (?:or |and )?([A-Z][\w]+)? ?(?:attachment|card attached)/.exec(card.s_text);
        if (atraitattachment) {
            if (atraitattachment[1]) {
                targets.push({ trait: atraitattachment[1].toLowerCase(), type: 'attachment' });
            }
            if (atraitattachment[2]) {
                targets.push({ trait: atraitattachment[2].toLowerCase(), type: 'attachment' });
            }
            return targets;
        }

        // Match targets like 'a hero'
        var acharacter = /(?: (?:a|an|1)) (hero|character|ally)(?! (?:you control )?with)/.exec(card.s_text);
        if (acharacter) {
            targets.push({ trait: 'none', type: acharacter[1] });
        }

        // Match targets like 'a Ranger hero'
        var atraithero = /(?:(?:a|an|1|Each|each|all|All) )([A-Z][\u00BF-\u1FFF\u2C00-\uD7FF\w]+) (?:or |and )?([A-Z][\u00BF-\u1FFF\u2C00-\uD7FF\w]+)? ?(?:hero)/.exec(card.s_text);
        if (atraithero) {
            if (atraithero[1]) {
                targets.push({ trait: atraithero[1].toLowerCase(), type: 'hero' });
            }

            if (atraithero[2]) {
                targets.push({ trait: atraithero[2].toLowerCase(), type: 'hero' });
            }
        }

        var atraitally = /(?:(?:a|an|1|first) )([A-Z][\u00BF-\u1FFF\u2C00-\uD7FF\w]+) (?:or |and )?([A-Z][\u00BF-\u1FFF\u2C00-\uD7FF\w]+)? ?(?:ally)/.exec(card.s_text);
        if (atraitally) {
            if (atraitally[1]) {
                targets.push({ trait: atraitally[1].toLowerCase(), type: 'ally' });
            }
            if (atraitally[2]) {
                targets.push({ trait: atraitally[2].toLowerCase(), type: 'ally' });
            }
        }

        var atraitcharacter = /(?:(?:a|an|1|defending|of|unique|each|Each|all|All) )([A-Z][\u00BF-\u1FFF\u2C00-\uD7FF\w]+) (?:or |and )?([A-Z][\u00BF-\u1FFF\u2C00-\uD7FF\w]+)? ?(?:character|deals|cards|you control)/.exec(card.s_text);
        if (atraitcharacter) {
            if (atraitcharacter[1]) {
                targets.push({ trait: atraitcharacter[1].toLowerCase(), type: 'character' });
            }
            if (atraitcharacter[2]) {
                targets.push({ trait: atraitcharacter[2].toLowerCase(), type: 'character' });
            }
        }
        // Match cards like 'with ranged' or 'sentinel character'
        var withranged = /(hero|character).+with (the |printed )?([R|r]anged)/.exec(card.s_text);
        if (withranged) {
            targets.push({ trait: 'ranged', type: withranged[1].toLowerCase() });
        }

        var withsentinel = /(hero|character).+with (the |printed )?([S|s]entinel)/.exec(card.s_text);
        if (withsentinel) {
            targets.push({ trait: 'sentinel', type: withsentinel[1].toLowerCase() });
        }

        var rangedcharacter = /[R|r]ranged character/.exec(card.s_text);
        if (rangedcharacter) {
            targets.push({ trait: 'ranged', type: 'character' });
        }

        var sentinelcharacter = /[S|s]entinel character/.exec(card.s_text);
        if (sentinelcharacter) {
            targets.push({ trait: 'sentinel', type: 'character' });
        }

        return targets;
    };

    // Gets list of targets options for the deck.
    suggestions.getTargetOptionsForDeck = function(cards) {
        var targetOptions = [];

        _.each(cards, function(card) {
            [].push.apply(targetOptions, suggestions.getTargetOptionsForCard(card));
        });

        return _.unique(targetOptions, function(item) {
            return item.trait + ':' + item.type;
        });
    };

    suggestions.getTraitSpecificTargetOptionsForDeck = function(targetsOptionsForDeck) {
        return _.filter(targetsOptionsForDeck, function(el) {
            return _.contains(suggestions.traits, el.trait);
        });
    };

    suggestions.getTraitSpecificTargetOptionsForCard = function(card) {
        return _.filter(suggestions.getTargetOptionsForCard(card), function(el) {
            return _.contains(suggestions.traits, el.trait);
        });
    };

    suggestions.getTargetOptionsForCard = function(card) {
        if (card.targetOptions) {
            return card.targetOptions;
        } else {
            var targetOptions = suggestions.getTargetOptionsForCardWithoutCache(card);
            app.data.cards.updateById(card.code, {
                targetOptions: targetOptions
            });
            return targetOptions;
        }
    };

    // Returns list of target options for the card. If Core Gloin was the card, the returned list would look like:
    // [{trait:'None',type:'hero'},
    //  {trait:'None',type:'character'},
    //  {trait:'Leadership',type:'hero'},
    //  {trait:'Leadership',type:'character'},
    //  {trait:'Dwarf',type:'hero'},
    //  {trait:'Dwarf',type:'character'},
    //  {trait:'Noble',type:'hero'},
    //  {trait:'Noble',type:'character'}]
    suggestions.getTargetOptionsForCardWithoutCache = function(card) {
        // Special case
        if (card.s_name == 'Beorn' && card.pack_code == 'OHaUH') {
            return []; // Beorn cannot be a target option for any player card
        }

        if (card.type_code == 'event' || card.type_code == 'player-side-quest') {
            return [];
        }

        var targets = [];
        var type = card.type_name.toLowerCase();
        var sphere = card.sphere_name.toLowerCase();

        targets.push({ trait: 'none', type: type });
        targets.push({ trait: 'none', type: 'character' });
        targets.push({ trait: sphere, type: type });
        targets.push({ trait: sphere, type: 'character' });

        if (card.s_traits) {
            var traits = card.s_traits.split(/[\. ]+/);
            _.each(traits, function(trait) {
                if (!trait) {
                    return;
                }

                targets.push({ trait: trait.toLowerCase(), type: type });
                targets.push({ trait: trait.toLowerCase(), type: 'character' });
            });
        }

        if (card.s_text) {
            if (card.s_text.match(/Ranged\./g)) {
                targets.push({ trait: 'ranged', type: type });
                targets.push({ trait: 'ranged', type: 'character' });
            }

            if (card.s_text.match(/Sentinel\./g)) {
                targets.push({ trait: 'sentinel', type: type });
                targets.push({ trait: 'sentinel', type: 'character' });
            }
        }

        return targets;
    };

    /**
     * @memberOf suggestions
     */
    suggestions.show = function show() {
        var table = $('#table-suggestions');
        var tbody = table.children('tbody').empty();

        if (!suggestions.number && table.is(':visible')) {
            table.hide();
            return;
        }

        if (suggestions.number && !table.is(':visible')) {
            table.show();
        }

        var nb = 0;
        for (var i = 0; i < suggestions.list.length; i++) {
            var card = app.data.cards.findById(suggestions.list[i].code);
            if (card.owned) {
                suggestions.div(card).on('click', 'button.close', suggestions.exclude.bind(this, card.code)).appendTo(tbody);

                if (++nb == suggestions.number) {
                    break;
                }
            }
        }
    };

    /**
     * @memberOf suggestions
     */
    suggestions.template = _.template(
        '<tr class="card-container" data-code="<%= code %>">' +
        '<td><button type="button" class="close"><span aria-hidden="true">&times;</span><span class="sr-only">Remove</span></button></td>' +
        '<td><div class="btn-group" data-toggle="buttons"><%= radios %></div></td>' +
        '<td><span class="icon icon-<%= sphere %> fg-<%= sphere %>"></span> <span class="icon icon-<%= type %>"></span></td>' +
        '<td><a class="card card-tip" data-code="<%= code %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= name %></a></td>' +
        '</tr>');

    suggestions.div = function div(record) {
        var radios = '';
        for (var i = 0; i <= record.maxqty; i++) {
            radios += '<label class="btn btn-xs btn-default' + (i == record.indeck ? ' active' : '') + '"><input type="radio" name="qty-' + record.code + '" value="' + i + '">' + i + '</label>';
        }

        return $(suggestions.template({
            code: record.code,
            name: record.name,
            type: record.type_code,
            sphere: record.sphere_code,
            radios: radios
        }));
    };

    /**
     * @memberOf suggestions
     */
    suggestions.exclude = function exclude(code) {
        suggestions.exclusions.push(suggestions.indexFromCodes[code]);
        suggestions.compute();
    };

    /**
     * @memberOf suggestions
     */
    suggestions.pick = function pick(event) {
        var input = this;
        $(input).closest('tr').animate({
            opacity: 0
        }, 'fast', function() {
            app.ui.on_list_quantity_change.call(input, event);
        });
    };

    suggestions.setup = function() {
        if (suggestions.initialized) {
            return;
        }

        suggestions.initialized = true;

        suggestions.query();

        $('#table-suggestions').on({
            change: suggestions.pick
        }, 'input[type=radio]');
    };

})(app.suggestions = {}, jQuery);
