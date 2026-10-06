(function app_suggestions(suggestions, $) {
    suggestions.heroesInDeck = [];
    suggestions.cardsInDeck = [];
    suggestions.cards = [];
    suggestions.spheresInDeck = [];
    suggestions.targetsInDeck = [];
    suggestions.traitSpecificTargetsInDeck = [];
    suggestions.targetOptionsForDeck = [];
    suggestions.traitSpecificTargetOptionsForDeck = [];
    suggestions.current = [];
    suggestions.list = [];
    suggestions.exclusions = [];
    suggestions.cardNamesInDeck = {};

    suggestions.number = 3;
    suggestions.isLoaded = false;
    suggestions.initialized = false;

    suggestions.traits = ['dwarf', 'rohan', 'silvan', 'noldor', 'gondor', 'ent', 'eagle', 'dunedain', 'hobbit', 'istari', 'outlands', 'ranger', 'scout', 'outlands', 'ranged', 'sentinel', 'weapon', 'noble', 'warrior'];
    suggestions.staples = [
        { name: "A Test of Will", pack_code: "Core" },
        { name: "Hasty Stroke", pack_code: "Core" },
        { name: "Steward of Gondor", pack_code: "Core" },
        { name: "Unexpected Courage", pack_code: "Core" },
        { name: "Daeron's Runes", pack_code: "FoS" },
        { name: "Deep Knowledge", pack_code: "VoI" },
        { name: "Feint", pack_code: "Core" },
        { name: "Gandalf", pack_code: "Core" }
    ];
    suggestions.vetoed = [
        { name: "Pippin", pack_code: "EaAD" },
        { name: "The End Comes", pack_code: "RtR" },
        { name: "Brok Ironfist", pack_code: "Core" }
    ];

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


    suggestions.compute = function compute() {
        if (suggestions.number) {

            suggestions.heroesInDeck = app.deck.get_hero_deck();
            suggestions.cardsInDeck = app.deck.get_cards();
            suggestions.cards = app.data.cards.find();

            suggestions.spheresInDeck = suggestions.getAvailableSpheres(suggestions.heroesInDeck);

            suggestions.targetsInDeck = suggestions.getTargetsInDeck(suggestions.cardsInDeck);
            suggestions.traitSpecificTargetsInDeck = suggestions.getTraitSpecificTargetsInDeck(suggestions.targetsInDeck);

            suggestions.targetOptionsForDeck = suggestions.getTargetOptionsForDeck(suggestions.cardsInDeck);
            suggestions.traitSpecificTargetOptionsForDeck = suggestions.getTraitSpecificTargetOptionsForDeck(suggestions.targetOptionsForDeck);

            suggestions.current = [];
            suggestions.list = [];

            var isDeckMonoSphere = _.unique(_.pluck(suggestions.heroesInDeck, 'sphere')).length == 1;
            var isDeckTriSphere = _.unique(_.pluck(suggestions.heroesInDeck, 'sphere')).length >= 3;
            //var deckMonoTraits = _.intersection.apply(_, _.map(_.pluck(suggestions.heroesInDeck, 's_traits'), function(h) { return h.split(/[\. ]+/) }));
            var isDeckSecrecy = suggestions.heroesInDeck.length <= 3 && app.deck.get_starting_threat() <= 20;

            _.each(suggestions.cards, function(card) {
                if (card.indeck > 0) {
                    return;
                }

                // Ignore excluded
                if (_.contains(suggestions.exclusions, card.code)) {
                    return;
                }

                // Ignore vetoed cards
                if (_.find(suggestions.vetoed, { name: card.name, pack_code: card.pack_code })) {
                    return;
                }

                var traitSpecificTargetsInCard = suggestions.getTraitSpecificTargetsInCard(card);
                var traitSpecificTargetOptionsForCard = suggestions.getTraitSpecificTargetOptionsForCard(card);

                // Suggest card if it's a staple
                if (_.find(suggestions.staples, { name: card.name, pack_code: card.pack_code })) {
                    return suggestions.current.push(card);
                }

                // Suggest cards that have a hero's name in the text
                if (card.s_text && suggestions.heroesInDeck.length) {
                    var regexp = new RegExp('\\b' + _.pluck(suggestions.heroesInDeck, 's_name').join('|') + '\\b', 'i');
                    if (card.s_text.match(regexp) && !card.s_name.match(regexp)) {
                        return suggestions.current.push(card);
                    }
                }

                var isMonoSphere = inStr(card.s_text, 'each hero you control', true) && inStr(card.s_text, 'icon', true);
                var isTriSphere = inStr(card.s_text, 'from 3 different', true);

                // Suggest mono sphere cards
                if (isDeckMonoSphere && isMonoSphere) {
                    return suggestions.current.push(card);
                }

                // Suggest tri sphere cards
                if (isDeckTriSphere && isTriSphere) {
                    return suggestions.current.push(card);
                }

                // Secrecy
                var isSecrecy = inStr(card.s_text, 'secrecy', true);
                if (isDeckSecrecy && isSecrecy) {
                    return suggestions.current.push(card);
                }

                // Suggest all allies of a trait if all the heroes have that trait
                // TODO

                var isTrap = inStr(card.traits, 'Trap');
                var heal = inStr(card.s_text, 'heal', true)
                var health = inStr(card.s_text, 'hit point', true);
                var isCostly = parseInt(card.cost, 10) > 4 && card.type_code != 'hero';
                var isSpiritHero = card.sphere_code == 'spirit' && card.type_code == 'hero';
                var isTacticsHero = card.sphere_code == 'tactics' && card.type_code == 'hero';
                var addsToVictoryDisplay = inStr(card.s_text, 'victory', true);
                var isDoomed = inStr(card.s_text, 'doomed', true);
                var reduceThreat = (inStr(card.s_text, 'lower', true) || inStr(card.s_text, 'reduce', true)) && inStr(card.s_text, 'threat', true);
                var explored = inStr(card.s_text, 'explored', true) || (inStr(card.s_text, 'progress', true) && inStr(card.s_text, 'location', true));
                var discards = inStr(card.s_text, 'discard pile', true) && !inStr(card.s_text, 'encounter', true);
                var encounter = inStr(card.s_text, 'top', true) && inStr(card.s_text, 'encounter deck', true);
                var isTacticsEvent = card.sphere_code == 'tactics' && card.type_code == 'event';
                var weaponOrArmor = inStr(card.s_traits, 'Weapon', true) || inStr(card.s_traits, 'Armor', true);
                var isEngagement = inStr(card.s_text, 'engage ', true) || inStr(card.s_text, 'into play engaged', true);
                var isEngagementCost = inStr(card.s_text, 'engagement cost ', true);
                var isDwarfSwarm = inStr(card.s_text, 'dwarf characters', true);

                _.find(suggestions.cardsInDeck, function(cardInDeck) {

                    // Suggest Trap cards for Damrod
                    if (isTrap && isCard(cardInDeck, 'damrod', 'LoS')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest heal/hit point cards for Gloin, Treebeard, Gimil
                    if ((heal || health) && (isCard(cardInDeck, 'gloin', 'Core') || isCard(cardInDeck, 'gimli', 'Core') || isCard(cardInDeck, 'treebeard', 'ToS') || isCard(cardInDeck, 'gimli', 'Core'))) {
                        return suggestions.current.push(card);
                    }

                    // Suggest healing cards for Elrond
                    if (heal && isCard(cardInDeck, 'elrond', 'SaF')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest high cost cards for Vilya
                    if (isCostly && isCard(cardInDeck, 'vilya', 'SaF')) {
                        return suggestions.list.push(card);  // Bypass basic checks
                    }

                    // Suggest spirit heroes for Caldara
                    if (isSpiritHero && isCard(cardInDeck, 'caldara', 'BoG')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest tactics heroes for Thoeden
                    if (isTacticsHero && isCard(cardInDeck, 'theoden', 'TMV')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest victory display for Rossiel
                    if (addsToVictoryDisplay && isCard(cardInDeck, 'rossiel', 'EfMG')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest doomed for Grima
                    if (isDoomed && isCard(cardInDeck, 'grima', 'VoI')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest threat reduction for Boromir, Dunhere, or Hobbit Gandalf
                    if (reduceThreat && (isCard(cardInDeck, 'boromir', 'TDM') || isCard(cardInDeck, 'gandalf', 'OHaUH') || isCard(cardInDeck, 'dunhere', 'Core'))) {
                        return suggestions.current.push(card);
                    }

                    // Suggest location cards for Idraen
                    if (explored && isCard(cardInDeck, 'idraen', 'TTT')) {
                        return suggestions.current.push(card);
                    }

                    // Sugest discard pile cards for Arwen or Erestor
                    if (discards && (isCard(cardInDeck, 'arwen undomiel', 'TDR') || isCard(cardInDeck, 'erestor', 'ToR'))) {
                        return suggestions.current.push(card);
                    }

                    // Sugest encounter cards for Denethor
                    if (encounter && isCard(cardInDeck, 'denethor', 'Core')) {
                        return suggestions.current.push(card);
                    }

                    // Sugest Tactics Events for Hama
                    if (isTacticsEvent && isCard(cardInDeck, 'hama', 'TLD')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest weapon/armour for Beregond
                    if (weaponOrArmor && isCard(cardInDeck, 'beregond', 'HoN')) {
                        return suggestions.current.push(card);
                    }

                    // Suggest engage cards
                    if (isEngagement && inStr(cardInDeck.s_text, 'engage ', true)) {
                        return suggestions.current.push(card);
                    }

                    if (isEngagementCost && inStr(cardInDeck.s_text, 'engagement cost', true)) {
                        return suggestions.current.push(card);
                    }

                    // Suggest Dwarf Swarm cards
                    if (isDwarfSwarm && inStr(cardInDeck.s_text, 'dwarf characters', true)) {
                        return suggestions.current.push(card);
                    }
                });

                // Suggest cards with similar traits. Example: If Eomund is in deck, suggest all characters with Rohan trait
                if (suggestions.matchInTargetLists(suggestions.traitSpecificTargetsInDeck, traitSpecificTargetOptionsForCard)) {
                    return suggestions.current.push(card);
                }

                if (suggestions.matchInTargetLists(suggestions.traitSpecificTargetOptionsForDeck, traitSpecificTargetsInCard)) {
                    return suggestions.current.push(card);
                }
            });

            suggestions.cardNamesInDeck = _.pluck(suggestions.cardsInDeck, 's_name');

            // Loop over list of suggested cards
            _.each(suggestions.current, function(suggestion) {
                if (suggestions.check(suggestion)) {
                    suggestions.list.push(suggestion);
                }
            });

        }
        suggestions.show();
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
        suggestions.exclusions.push(code);
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

        $('#table-suggestions').on({
            change: suggestions.pick
        }, 'input[type=radio]');
    };

})(app.suggestions = {}, jQuery);
