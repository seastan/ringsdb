(function app_deck(deck, $) {

    var date_creation;
    var date_update;
    var description_md;
    var id;
    var name;
    var tags;
    var unsaved;
    var user_id;
    var is_published;
    var freeze_comments;

    var header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>');
    var card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> fg-<%= card.sphere_code %>"></span> <a href="<%= card.url %>" class="card card-tip fg-<%= card.sphere_code %>" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>');
    var layouts = {};
    var display_full_pack_info = false;
    var use_pack_code = false;

    /*
     * Templates for the different deck layouts, see deck.get_layout_data
     */

    layouts.type = {};
    layouts.type[1] = _.template('<div class="deck-content"><%= meta %><%= heroes %><%= contract %><%= objective %><%= allies %><%= attachments %><%= events %><%= sidequests %><%= treasures %></div>');
    layouts.type[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-12"><%= meta %></div></div><div class="row"><div class="col-sm-12"><%= heroes %></div></div><div class="row"><div class="col-sm-6 col-print-6"><%= contract %><%= objective %><%= allies %></div><div class="col-sm-6 col-print-6"><%= attachments %><%= events %><%= sidequests %><%= treasures %></div></div></div>');
    layouts.type[3] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-4"><%= meta %><%= heroes %></div><div class="col-sm-4"><%= objective %><%= contract %><%= allies %></div><div class="col-sm-4"><%= attachments %><%= events %><%= sidequests %><%= treasures %></div></div></div>');

    layouts.position = {};
    layouts.position[1] =  function(data) {
        var layout = [];
        layout.push('<div class="deck-content">');

        _.forEach(data, function(el) {
            layout.push(el);
        });

        layout.push('</div>');
        return layout.join('');
    };
    layouts.sphere = layouts.name = layouts.position;

    $(document).on('click', '.expand-packs', function() {
        display_full_pack_info = true;
        app.ui.refresh_deck();
    });


    deck.problem_labels = {
        too_many_heroes: "Contains too many heroes",
        too_few_heroes: "Contains too few heroes",
        too_many_copies: "Contains too many copies of a card (by title)",
        invalid_for_tournament_play: "Invalid for tournament play for having less than 50 cards",
        duplicated_unique_heroes: "More than one hero with the same unique name",
        too_few_cards: "Contains too few cards",
        invalid_cards: "Contains forbidden cards"
    };

    /**
     * @memberOf deck
     */
    deck.init = function(data) {
        date_creation = data.date_creation;
        date_update = data.date_update;
        description_md = data.description_md;
        id = data.id;
        name = data.name;
        tags = data.tags;
        unsaved = data.unsaved;
        user_id = data.user_id;
        is_published = data.is_published;
        freeze_comments = data.freeze_comments;

        if (app.data.isLoaded) {
            deck.set_slots(data.slots, data.sideslots);
        } else {
            $(document).on('data.app', function () {
                deck.set_slots(data.slots, data.sideslots);
            });
        }
    };

    deck.setMeta = function(data) {
        id = data.id || null;
        name = data.name || '';
        description_md = data.description_md || data.description || '';
        tags = data.tags || '';
    };

    deck.set_slots = function(slots, sideslots) {
        app.data.cards.update({}, {
            indeck: 0,
            insideboard: 0
        });

        for (var code in slots) {
            if (slots.hasOwnProperty(code)) {
                app.data.cards.updateById(code, {
                    indeck: slots[code]
                });
            }
        }

        for (var side_code in sideslots) {
            if (sideslots.hasOwnProperty(side_code)) {
                app.data.cards.updateById(side_code, {
                    insideboard: sideslots[side_code]
                });
            }
        }
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_id = function() {
        return id;
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_name = function() {
        return name;
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_is_published = function() {
        return is_published;
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_description_md = function() {
        return description_md;
    };

    /**
     * @memberOf deck
     * @returns boolean
     */
    deck.get_freeze_comments = function() {
        return freeze_comments;
    };

    /**
     * @memberOf deck
     */
    deck.get_cards = function(sort, query) {
        sort = sort || {};
        sort['code'] = 1;

        query = query || {};
        query.indeck = {
            '$gt': 0
        };

        return app.data.cards.find(query, {
            '$orderBy': sort
        });
    };

    deck.get_side_cards = function(sort, query) {
        sort = sort || {};
        sort['code'] = 1;

        query = query || {};
        query.insideboard = {
            '$gt': 0
        };

        return app.data.cards.find(query, {
            '$orderBy': sort
        });
    };

    /**
     * @memberOf deck
     */
    deck.get_draw_deck = function(sort) {
        return deck.get_cards(sort, {
            type_code: {
                '$nin': ['hero']
            }
        });
    };



    /**
     * @memberOf deck
     */
    deck.get_draw_deck_size = function(sort) {
        var draw_deck = deck.get_draw_deck(sort);
        return deck.get_nb_cards(draw_deck);
    };

    /**
     * @memberOf deck
     */
    deck.get_hero_deck = function(primarySpheresOnly, sort) {
        var where = {
            type_code: 'hero'
        };

        if (primarySpheresOnly) {
            where.sphere_code = {
                '$nin': ['baggins', 'fellowship']
            };
        }

        return deck.get_cards(sort, where);
    };

    /**
     * @memberOf deck
     */
    deck.get_hero_deck_size = function(primarySpheres, sort) {
        var hero_deck = deck.get_hero_deck(primarySpheres, sort);
        return deck.get_nb_cards(hero_deck);
    };

    deck.get_heroes_spheres = function() {
        var heroes = deck.get_hero_deck();
        return _.uniq(_.pluck(heroes, 'sphere_code'));
    };

    deck.get_starting_threat = function() {
        var hero_deck = deck.get_hero_deck();
        var threat = _.pluck(hero_deck, 'threat');
        var total = _.reduce(threat, function(memo, num) { return memo + num; }, 0);

        // Reduce threat for Mirlonde
        var mirlonde = _.find(hero_deck, { name: 'Mirlonde', pack_code: 'TDF' });
        if (mirlonde) {
            _.each(hero_deck, function(hero) {
                if (hero.sphere_code == 'lore' && !hero.name.includes('MotK')) {
                    total--;
                }
            });
        }
        // Reduce threat for Folco
        var folco = _.find(hero_deck, { name: 'Folco Boffin', pack_code: 'DoCG' });
        if (folco) {
            _.each(hero_deck, function(hero) {
                if (hero.traits.includes('Hobbit') && !hero.name.includes('MotK')) {
                    total--;
                }
            });
        }
        return total;
    };

    deck.get_nb_cards = function(cards, is_sideboard) {
        if (!cards) {
            cards = is_sideboard ? deck.get_side_cards() : deck.get_cards();
        }
        // Remove contracts, treasures and player objectives from card count
        var num_contracts = 0;
        _.each(cards, function(card) {
            if ((card.cost == '-') || (((card.type_code == 'contract') || (card.type_code == 'player-objective') || (card.type_code == 'treasure')) && (card.cost == null))) num_contracts += card.indeck;
        })
        var quantities = _.pluck(cards, is_sideboard ? 'insideboard' : 'indeck');
        return _.reduce(quantities, function(memo, num) { return memo + num; }, 0) - num_contracts;
    };

    /**
     * @memberOf deck
     */
    deck.get_included_packs = function(is_sideboard) {
        var cards = is_sideboard ? deck.get_side_cards() : deck.get_cards();
        var nb_packs = {};

        cards.forEach(function(card) {
            nb_packs[card.pack_code] = Math.max(nb_packs[card.pack_code] || 0, Math.ceil(card.indeck / card.quantity));
        });

        var pack_codes = _.uniq(_.pluck(cards, 'pack_code'));

        var packs = app.data.packs.find({
            'code': {
                '$in': pack_codes
            }
        }, {
            '$orderBy': {
                'available': 1
            }
        });

        packs.forEach(function(pack) {
            pack.quantity = nb_packs[pack.code] || 0;
        });

        return packs;
    };

    deck.get_lastest_pack = function(is_sideboard) {
        var packs = deck.get_included_packs(is_sideboard);
        return packs[packs.length - 1] || {};
    };

    /**
     * @memberOf deck
     */
    deck.display = function(container, options, is_sideboard) {
        options = _.extend({ sort: 'type', cols: 2 }, options);

        var layout_data = is_sideboard ? deck.get_side_layout_data(options) : deck.get_layout_data(options);
        var deck_content = layout_data ? layouts[options.sort][options.cols](layout_data) : '';

        var c = $(container).removeClass('deck-loading').empty().append(deck_content);

        if (options.cols == 1) {
            c.find('.hero-thumbnail').removeClass('card-thumbnail-2x').addClass('card-thumbnail-3x');
        } else if (options.cols == 2) {
            c.find('div.col-sm-6:empty').remove();
        }
    };

    deck.get_side_layout_data = function(options) {
        var data = {
        };

        if (!deck.get_side_cards().length) {
            return null;
        }

        var packs = deck.get_included_packs(true);

        var title = $('<h4 style="font-weight: bold">Sideboard</h4>');
        deck.update_layout_section(data, 'meta', title);

        if (options.sort == 'type') {
            deck.update_layout_section(data, 'heroes', deck.get_layout_data_one_section('type_code', 'hero', 'type_name', true));
            deck.update_layout_section(data, 'allies', deck.get_layout_data_one_section('type_code', 'ally', 'type_name', true));
            deck.update_layout_section(data, 'attachments', deck.get_layout_data_one_section('type_code', 'attachment', 'type_name', true));
            deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name', true));
            deck.update_layout_section(data, 'sidequests', deck.get_layout_data_one_section('type_code', 'player-side-quest', 'type_name', true));
            deck.update_layout_section(data, 'objective', deck.get_layout_data_one_section('type_code', 'player-objective', 'type_name', true));
            deck.update_layout_section(data, 'contract', deck.get_layout_data_one_section('type_code', 'contract', 'type_name', true));
            deck.update_layout_section(data, 'treasures', deck.get_layout_data_one_section('type_code', 'treasure', 'type_name', true));
        }

        if (options.sort == 'position') {
            packs.forEach(function(pack) {
                deck.update_layout_section(data, pack.code, deck.get_layout_data_one_section('pack_code', pack.code, 'pack_name', true));
            });
        }

        if (options.sort == 'sphere') {
            ['leadership', 'lore', 'spirit', 'tactics', 'neutral', 'baggins', 'fellowship'].forEach(function(sphere) {
                deck.update_layout_section(data, sphere, deck.get_layout_data_one_section('sphere_code', sphere, 'sphere_name', true));
            });
        }

        if (options.sort == 'name') {
            deck.update_layout_section(data, 'name', deck.get_layout_data_one_section('name', null, 'Cards', true));
        }

        return data;
    };

    deck.get_layout_data = function(options) {
        var data = {
        };

        var problem = deck.get_problem();
        var herocount = deck.get_hero_deck_size();
        var drawcount = deck.get_draw_deck_size();

        var title;
        if (options.special_meta) {
            var url;

            if (deck.get_is_published()) {
                url = Routing.generate('decklist_detail', {
                    decklist_id: deck.get_id()
                });
            } else {
                url = Routing.generate('deck_view', {
                    deck_id: deck.get_id()
                });
            }
            // If published or is_author, generate url to deck.
            if (is_published) {
                var link = $('<a target="_blank"></a>').attr('href', url).text(deck.get_name());
            } else if (app.user.data && app.user.data.is_author) {
                var link = $('<a target="_blank"></a>').attr('href', url).text(deck.get_name());
            } else {
                var link = '<i>'+deck.get_name()+'</i> <small>(unpublished)</small>';
            }
/*             if (is_published) {
                var link = $('<a target="_blank"></a>').attr('href', url).text(deck.get_name());
            } else {
                var link = '<i>'+deck.get_name()+'</i> <small>(unpublished)</small>';
            } */
            title = $('<h4 style="height: 38px"></h4>').append(link);
        } else {
            title = $('<h4 style="font-weight: bold">Main Deck</h4>');
        }
        var threat = $('<div>Starting Threat: <b>' + deck.get_starting_threat() + '</b></div>')

        var text = [herocount, herocount == 1 ? ' Hero, ': ' Heroes, ', drawcount, drawcount == 1 ? ' Card': ' Cards' ].join(' ');
        var sizeinfo = $('<div class="deckcardcount">' + text + '</div>');

        if (drawcount < 50 || herocount == 0 || deck.get_hero_deck_size(true) > 3) {
            sizeinfo.addClass('text-danger');
        }

        var packs = deck.get_included_packs();

        var packinfo;
        if (display_full_pack_info) {
            packinfo = $('<div>Packs: ' + _.map(packs, function (pack) { return pack.name + (pack.quantity > 1 ? ' (' + pack.quantity + ')' : ''); }).join(', ') + '</div>');
        } else {
            packinfo = $('<div class="latestpack">Cards up to <i>' + (deck.get_lastest_pack().name || '-') + '</i></div>');

            if (packs.length) {
                $('<small><i style="cursor: pointer; padding-left: 5px;" class="fa fa-eye expand-packs"></i></small>').appendTo(packinfo);
            }
        }

        deck.update_layout_section(data, 'meta', title);
        //deck.update_layout_section(data, 'meta', $('<br>'));
        deck.update_layout_section(data, 'meta', threat);
        deck.update_layout_section(data, 'meta', sizeinfo);

        if (!options.special_meta) {
            deck.update_layout_section(data, 'meta', packinfo);

            if (problem) {
                var probleminfo = $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> ' + deck.problem_labels[problem] + '</div>');
                deck.update_layout_section(data, 'meta', probleminfo);
            }
        }

        if (options.sort == 'type') {
            deck.update_layout_section(data, 'heroes', deck.get_layout_data_one_section('type_code', 'hero', 'type_name'));

            if (!options.header_only) {
                deck.update_layout_section(data, 'allies', deck.get_layout_data_one_section('type_code', 'ally', 'type_name'));
                deck.update_layout_section(data, 'attachments', deck.get_layout_data_one_section('type_code', 'attachment', 'type_name'));
                deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name'));
                deck.update_layout_section(data, 'sidequests', deck.get_layout_data_one_section('type_code', 'player-side-quest', 'type_name'));
                deck.update_layout_section(data, 'objective', deck.get_layout_data_one_section('type_code', 'player-objective', 'type_name'));
                deck.update_layout_section(data, 'contract', deck.get_layout_data_one_section('type_code', 'contract', 'type_name'));
                deck.update_layout_section(data, 'treasures', deck.get_layout_data_one_section('type_code', 'treasure', 'type_name'));
            } else {
                deck.update_layout_section(data, 'allies', '');
                deck.update_layout_section(data, 'attachments', '');
                deck.update_layout_section(data, 'events', '');
                deck.update_layout_section(data, 'sidequests', '');
                deck.update_layout_section(data, 'objective', '');
                deck.update_layout_section(data, 'contract', '');
                deck.update_layout_section(data, 'treasures', '');
            }
        }

        if (options.sort == 'position') {
            packs.forEach(function(pack) {
                deck.update_layout_section(data, pack.code, deck.get_layout_data_one_section('pack_code', pack.code, 'pack_name'));
            });
        }

        if (options.sort == 'sphere') {
            ['leadership', 'lore', 'spirit', 'tactics', 'neutral', 'baggins', 'fellowship'].forEach(function(sphere) {
                deck.update_layout_section(data, sphere, deck.get_layout_data_one_section('sphere_code', sphere, 'sphere_name'));
            });
        }

        if (options.sort == 'name') {
            deck.update_layout_section(data, 'name', deck.get_layout_data_one_section('name', null, 'Cards'));
        }

        return data;
    };

    deck.update_layout_section = function(data, section, element) {
        if (!data[section]) {
            data[section] = '';
        }

        if (!element) {
            return '';
        }

        data[section] += element[0].outerHTML;
    };

    deck.get_layout_data_one_section = function(sortKey, sortValue, displayLabel, is_sideboard) {
        var section = $('<div />');
        var query = {};

        var getCards = is_sideboard ? deck.get_side_cards : deck.get_cards;
        var key = is_sideboard ? 'insideboard' : 'indeck';

        if (sortValue) {
            query[sortKey] = sortValue;
        }

        var cards;

        if (sortKey == 'type_code' || sortKey == 'name') {
            cards = getCards({ name: 1 }, query);
        } else if (sortKey == 'pack_code') {
            cards = getCards({ position: 1 }, query);
        } else {
            cards = getCards({ type_code: 1, code: 1 }, query);
        }

        if (!cards.length) {
            return null;
        }

        $(header_tpl({ code: sortValue, name: cards[0][displayLabel] || 'Cards', quantity: deck.get_nb_cards(cards, is_sideboard) })).appendTo(section);

        function getConflictTitle(card) {
            var ownedCopies = (card.owned_copies != null) ? card.owned_copies : 999;
            var conflictQty = (app.multiDeck && app.multiDeck.decks.length > 1)
                ? app.multiDeck.getCombinedQty(card.code)
                : card[key];
            if (conflictQty <= ownedCopies) return null;
            return (app.multiDeck && app.multiDeck.decks.length > 1)
                ? 'Total across all decks (' + conflictQty + ') exceeds your ' + ownedCopies + ' owned cop' + (ownedCopies === 1 ? 'y' : 'ies')
                : 'You own only ' + ownedCopies + ' cop' + (ownedCopies === 1 ? 'y' : 'ies') + ' of this card';
        }

        cards.forEach(function(card) {
            if (card.type_code == 'hero' && sortKey == 'type_code' && !is_sideboard) {
                var div = $('<div class="deck-hero"/>')
                    .append('<div class="hero-thumbnail card-thumbnail-2x card-thumbnail-hero" style="background-image:url(\'/bundles/cards/' + card.code + '.png\')"></div>')
                    .append($(card_line_tpl({ card: card })));

                div.appendTo(section);
            } else {
                var tpl = $(card_line_tpl({ card: card }));

                var conflictTitle = getConflictTitle(card);
                var countSpan = $('<span class="card-count">' + card[key] + 'x</span> ');
                if (conflictTitle) {
                    countSpan.addClass('deck-conflict').attr('title', conflictTitle);
                }

                var div = $('<div />').append(tpl).prepend(sortKey != 'type_code' || card.type_code != 'hero' ? countSpan : '');

                if (card.is_unique && card.type_code != 'hero') {
                    div.find('a').css('font-weight', 'bold');
                }

                if (!deck.can_include_card(card)) {
                    div.addClass('invalid-card');
                }

                if (sortKey == 'pack_code') {
                    div.append(' <small class="text-muted" style="padding-left: 2px">(#' + card.position + ')</small>');
                }

                div.appendTo(section);
            }
        });

        if (sortKey == 'type_code') {
            section.find('.deck-hero').wrapAll('<div class="hero-deck-list"/>');
            section.append('<div style="clear: both;" />');
        }

        return section;
    };

    /**
     * @memberOf deck
     * @return integer final count indeck for card_code. each copy in sideboard removes one from the deck
     */
    deck.set_card_copies = function(card_code, nb_copies, is_sideboard) {
        var card = app.data.cards.findById(card_code);
        if (!card) {
            return false;
        }

        var update = {};

        if (!is_sideboard) {
            update.indeck = nb_copies;
            update.insideboard = Math.min(card.insideboard, card.maxqty - nb_copies);
        } else {
            update.insideboard = nb_copies;
            update.indeck = Math.min(card.indeck, card.maxqty - nb_copies);
        }

        app.data.cards.updateById(card_code, update);
        app.deck_history && app.deck_history.notify_change();

        return update.indeck;
    };

    /**
     * @memberOf deck
     */
    deck.get_content = function() {
        var cards = deck.get_cards();
        var side_cards = deck.get_side_cards();

        var content = {
            main: {},
            side: {}
        };

        cards.forEach(function(card) {
            content.main[card.code] = card.indeck;
        });

        side_cards.forEach(function(card) {
            content.side[card.code] = card.insideboard;
        });

        return content;
    };

    /**
     * @memberOf deck
     */
    deck.get_json = function() {
        return JSON.stringify(deck.get_content());
    };

    /**
     * @memberOf deck
     */
    deck.get_export = function(format) {

    };

    deck.get_copies_and_deck_limit = function() {
        var copies_and_deck_limit = {};
        deck.get_cards().forEach(function(card) {
            var cardname = card.name;
            if (card.type_code == 'hero') {
                cardname = cardname + 'Hero';
            }
            var value = copies_and_deck_limit[cardname];
            if (!value) {
                copies_and_deck_limit[cardname] = {
                    nb_copies: card.indeck,
                    deck_limit: card.deck_limit
                };
            } else {
                value.nb_copies += card.indeck;
                value.deck_limit = Math.min(card.deck_limit, value.deck_limit);
            }
        });
        return copies_and_deck_limit;
    };

    /**
     * @memberOf deck
     */
    deck.get_problem = function() {
        // exactly 7 plots
        var herocount = deck.get_hero_deck_size(true);

        // Removed check due to Bond of Friendship Contract
        // if (herocount > 3) {
        //     return 'too_many_heroes';
        // }

        if (herocount < 1) {
            return 'too_few_heroes';
        }

        var heroes = deck.get_hero_deck();
        var unique =  _.uniq(_.pluck(heroes, 'name'));

        if (heroes.length != unique.length) {
            return 'duplicated_unique_heroes';
        }

        // at least 50 others cards
        var decksize = deck.get_draw_deck_size();
        if (decksize < 30) {
            return 'too_few_cards';
        } else if (decksize < 50) {
            return 'invalid_for_tournament_play';
        }

        var keys = _.findKey(deck.get_copies_and_deck_limit(), function(value) {
            return value.nb_copies > value.deck_limit;
        });

        if (keys != null) {
            return 'too_many_copies';
        }


        // no invalid card
        if (deck.get_invalid_cards().length > 0) {
            return 'invalid_cards';
        }
    };

    deck.get_invalid_cards = function() {
        return _.filter(deck.get_cards(), function(card) {
            return !deck.can_include_card(card);
        });
    };

    /**
     * returns true if the deck can include the card as parameter
     * @memberOf deck
     */
    deck.can_include_card = function(card) {
        return true;
    };

    deck.i_have_this_card = function(card) {
        return card.owned;
    };

    deck.export_bbcode = function(skip_title) {
        $('#export-deck').html(deck.build_bbcode(skip_title).join("\n"));
        $('#exportModal').modal('show');
    };

    deck.build_bbcode = function(skip_title) {
        var lines = [];

        if (!skip_title) {
            lines.push('[b]' + deck.get_name() + '[/b]');
            lines.push('');
        }

        $('.deck-content').each(function() {
            var content = $(this);

            content.find('h4:visible, h5:visible').each(function(i, type) {
                if (type.tagName.toLowerCase() == 'h4') {
                    lines.push('[i]' + $(type).text().trim() + '[/i]');
                    lines.push('');
                    return;
                } else {
                    lines.push('[b]' + $(type).text().trim() + '[/b]');
                }

                $(this).parent().find('> div:not(.hero-deck-list), .hero-deck-list > div').each(function(j, line) {
                    var line = $(line);
                    var qty = line.find('.card-count').text().trim();
                    var card = app.data.cards.findById(line.find('a.card').data('code'));

                    if (card) {
                        var str = qty + ' [url=http://ringsdb.com/card/' + card.code + ']' + card.name + '[/url] [i](' + card.pack_name + ')[/i]';
                        lines.push(str.trim());
                    }
                });
                lines.push('');
            });


            var count = content.find('.deckcardcount').text();
            if (count) {
                lines.push(count);
            }

            var latestpack = content.find('.latestpack').text();
            if (latestpack) {
                lines.push(latestpack);
            }
            lines.push('');
        });

        var type = app.user.params.decklist_id ? 'Decklist' : app.user.params.fellowship_id ? 'Fellowship' : 'Deck';
        var social = (app.user.params.decklist_id || (app.user.params.fellowship_id && Fellowship.published));

        if (social) {
            lines.push(type + ' [url='+location.href+']built and published on RingsDB[/url].');
        } else {
            lines.push(type + ' built on [url=http://ringsdb.com]RingsDB[/url].');
        }

        return lines;
    };

    deck.export_markdown = function(skip_title) {
        $('#export-deck').html(deck.build_markdown(skip_title).join('\n'));
        $('#exportModal').modal('show');
    };

    deck.build_markdown = function(skip_title) {
        var lines = [];

        if (!skip_title) {
            lines.push('#' + deck.get_name());
            lines.push('');
        }

        $('.deck-content').each(function() {
            var content = $(this);

            content.find('h4:visible, h5:visible').each(function(i, type) {
                if (type.tagName.toLowerCase() == 'h4') {
                    lines.push('##*' + $(type).text().trim() + '*');
                    lines.push('');
                    return;
                } else {
                    lines.push('###' + $(type).text().trim());
                }

                $(this).parent().find('> div:not(.hero-deck-list), .hero-deck-list > div').each(function(j, line) {
                    var line = $(line);
                    var qty = line.find('.card-count').text().trim();
                    var card = app.data.cards.findById(line.find('a.card').data('code'));

                    if (card) {
                        lines.push('* ' + qty + ' [' + card.name + '](http://ringsdb.com/card/' + card.code + ') _(' + card.pack_name + ')_');
                    }
                });

                lines.push('');
            });


            var count = content.find('.deckcardcount').text();
            if (count) {
                lines.push(count);
            }

            var latestpack = content.find('.latestpack').text();
            if (latestpack) {
                lines.push(latestpack);
            }
            lines.push('');
        });

        var type = app.user.params.decklist_id ? 'Decklist' : app.user.params.fellowship_id ? 'Fellowship' : 'Deck';
        var social = (app.user.params.decklist_id || (app.user.params.fellowship_id && Fellowship.published));

        if (social) {
            lines.push(type + ' [built and published on RingsDB]('+location.href+').');
        } else {
            lines.push(type + ' built on [RingsDB](http://ringsdb.com).');
        }

        return lines;
    };

    deck.export_plaintext = function(skip_title) {
        $('#export-deck').html(deck.build_plaintext(skip_title).join('\n'));
        $('#exportModal').modal('show');
    };

    deck.build_plaintext = function(skip_title) {
        var lines = [];

        if (!skip_title) {
            lines.push(deck.get_name());
            lines.push('');
        }

        $('.deck-content').each(function() {
            var content = $(this);

            content.find('h4:visible, h5:visible').each(function(i, type) {
                lines.push($(this).text().trim());

                if (type.tagName.toLowerCase() == 'h4') {
                    lines.push('');
                    return;
                }

                $(this).parent().find('> div:not(.hero-deck-list), .hero-deck-list > div').each(function(j, line) {
                    var line = $(line);
                    var card = app.data.cards.findById(line.find('a.card').data('code'));

                    var text = line.text().replace(/\s+\([^#].*\)/, '').trim();

                    if (card) {
                        lines.push(text + ' (' + card.pack_name + ')');
                    }
                });
                lines.push('');
            });

            var count = content.find('.deckcardcount').text();
            if (count) {
                lines.push(count);
            }

            var latestpack = content.find('.latestpack').text();
            if (latestpack) {
                lines.push(latestpack);
            }
            lines.push('');
        });

        var type = app.user.params.decklist_id ? 'Decklist' : app.user.params.fellowship_id ? 'Fellowship' : 'Deck';
        var social = (app.user.params.decklist_id || (app.user.params.fellowship_id && Fellowship.published));

        if (social) {
            lines.push(type + ' built and published on ' + location.href);
        } else {
            lines.push(type + ' built on http://ringsdb.com.');
        }

        return lines;
    };


    deck.export_html = function(skip_title) {
        $('#export-deck').html(deck.build_html(skip_title).join("\n"));
        $('#exportModal').modal('show');
    };

    deck.build_html = function(skip_title) {
        var lines = [];

        if (!skip_title) {
            lines.push('<p><strong>' + deck.get_name() + '</strong></p>');
        }

        $('.deck-content').each(function() {
            var content = $(this);

            content.find('h4:visible, h5:visible').each(function(i, type) {
                if (type.tagName.toLowerCase() == 'h4') {
                    lines.push('<p><strong>' + $(type).text().trim() + '</strong></p>');
                    return;
                } else {
                    lines.push('<p>');
                    lines.push('<span style="text-decoration: underline;"><strong>' + $(type).text().trim() + '</strong></span>');
                }

                $(this).parent().find('> div:not(.hero-deck-list), .hero-deck-list > div').each(function(j, line) {
                    var line = $(line);
                    var qty = line.find('.card-count').text().trim();
                    var card = app.data.cards.findById(line.find('a.card').data('code'));

                    if (card) {
                        var color = '#000000';

                        switch (card.sphere_code) {
                            case 'tactics':
                                color = '#FF0000';
                                break;
                            case 'spirit':
                                color = '#00B1D4';
                                break;
                            case 'lore':
                                color = '#51B848';
                                break;
                            case 'leadership':
                                color = '#AD62A5';
                                break;
                            case 'neutral':
                                color = '#616161';
                                break;
                            case 'baggins':
                                color = '#B39E26';
                                break;
                            case 'fellowship':
                                color = '#B56C0C';
                                break;
                        }

                        lines.push('<br>');
                        var str = qty + ' <a href="http://ringsdb.com/card/' + card.code + '" style="color: ' + color + ';" target="_blank">' + card.name + '</a> (' + (use_pack_code ? card.pack_code : card.pack_name) + ')';
                        lines.push(str.trim());
                    }
                });

                lines.push('</p>');
            });

            var count = content.find('.deckcardcount').text();
            var latestpack = content.find('.latestpack').text();

            if (count || latestpack) {
                lines.push('<p>');
            }

            if (count) {
                lines.push(count);
            }

            if (count && latestpack) {
                lines.push('<br>');
            }

            if (latestpack) {
                lines.push(latestpack);
            }

            if (count || latestpack) {
                lines.push('</p>');
            }
        });

        var type = app.user.params.decklist_id ? 'Decklist' : app.user.params.fellowship_id ? 'Fellowship' : 'Deck';
        var social = (app.user.params.decklist_id || (app.user.params.fellowship_id && Fellowship.published));

        if (social) {
            lines.push('<p>' + type + ' <a href="' + location.href + '" target="_blank">built and published on RingsDB</a>.</p>');
        } else {
            lines.push('<p>' + type + ' built on <a href="http://ringsdb.com" target="_blank">RingsDB</a>.</p>');
        }

        return lines;
    };
    
    deck.use_pack_code = function() {
        use_pack_code = true;
    };
})(app.deck = {}, jQuery);

// Multi-deck build module. Sits on top of app.deck and tracks N decks
// simultaneously so combined card usage can be compared against owned copies.
app.multiDeck = {
    decks: [],
    activeIdx: 0,

    init: function() {
        var deckName = app.deck.get_name() || '';
        if (deckName === 'New Lord of the Rings LCG Deck') deckName = 'New Deck';
        jQuery('input.decklist-name').val(deckName);
        app.multiDeck.decks = [{
            id: app.deck.get_id(),
            name: deckName,
            description_md: jQuery('textarea[name="description_"]').val() || app.deck.get_description_md() || '',
            tags: jQuery('input[name="tags_"]').val() || '',
            slots: {},
            sideslots: {},
            history: app.deck_history ? app.deck_history.get_snapshots().slice() : [],
            uiState: null
        }];
        app.multiDeck.activeIdx = 0;
        app.multiDeck.render_tabs();

        var alsoMatch = window.location.search.match(/[?&]also=([^&]+)/);
        if (alsoMatch) {
            var additionalIds = alsoMatch[1].split(',');
            function loadNext(index) {
                if (index >= additionalIds.length) return;
                var id = additionalIds[index].trim();
                if (!id) { loadNext(index + 1); return; }
                jQuery.ajax(Routing.generate('api_private_load_deck', { id: id }), {
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    success: function(deckData) {
                        app.multiDeck.addExistingDeck(deckData);
                        loadNext(index + 1);
                    }
                });
            }
            loadNext(0);
        }
    },

    // Snapshot the live ForerunnerDB state and all UI state into decks[activeIdx].
    captureActive: function() {
        var d = app.multiDeck.decks[app.multiDeck.activeIdx];
        var nameVal = jQuery('input.decklist-name').val();
        if (nameVal !== undefined) d.name = nameVal;
        d.description_md = jQuery('textarea[name="description_"]').val() || '';
        d.tags = jQuery('input[name="tags_"]').val() || '';

        if (app.deck_history) {
            d.history = app.deck_history.get_snapshots().slice();
        }

        if (app.ui && app.ui.capture_filter_state) {
            d.uiState = app.ui.capture_filter_state();
        }

        var slots = {};
        var sideslots = {};
        app.data.cards.find({ indeck: { '$gt': 0 } }).forEach(function(card) {
            slots[card.code] = card.indeck;
        });
        app.data.cards.find({ insideboard: { '$gt': 0 } }).forEach(function(card) {
            sideslots[card.code] = card.insideboard;
        });
        d.slots = slots;
        d.sideslots = sideslots;
    },

    // Switch the active deck. Snapshots current state first.
    activateDeck: function(idx) {
        if (idx === app.multiDeck.activeIdx) return;
        app.multiDeck.captureActive();
        app.multiDeck.activeIdx = idx;
        var d = app.multiDeck.decks[idx];
        app.deck.setMeta(d);
        app.deck.set_slots(d.slots, d.sideslots);
        jQuery('input.decklist-name').val(d.name || '');
        jQuery('#deck-save-id').val(d.id || '');
        jQuery('textarea[name="description_"]').val(d.description_md || '');
        jQuery('input[name="tags_"]').val(d.tags || '');
        app.deck_history && app.deck_history.reset(d.history || []);
        app.ui && app.ui.restore_filter_state && app.ui.restore_filter_state(d.uiState || null);
        app.ui && app.ui.refresh_deck && app.ui.refresh_deck();
        app.ui && app.ui.reset_list && app.ui.reset_list();
        app.multiDeck.render_tabs();
    },

    // Append a new empty deck and switch to it.
    addDeck: function() {
        app.multiDeck.captureActive();
        var newDeck = {
            id: null,
            name: 'New Deck ' + (app.multiDeck.decks.length + 1),
            description_md: '',
            tags: '',
            slots: {},
            sideslots: {},
            history: [],
            uiState: null
        };
        app.multiDeck.decks.push(newDeck);
        app.multiDeck.activeIdx = app.multiDeck.decks.length - 1;
        app.deck.setMeta(newDeck);
        app.deck.set_slots({}, {});
        app.deck_history && app.deck_history.reset([]);
        jQuery('input.decklist-name').val(newDeck.name);
        jQuery('#deck-save-id').val('');
        jQuery('textarea[name="description_"]').val('');
        jQuery('input[name="tags_"]').val('');
        app.ui && app.ui.apply_default_filters && app.ui.apply_default_filters();
        jQuery('#save_form button[type=submit]').first().text('Save All');
        app.ui && app.ui.refresh_deck && app.ui.refresh_deck();
        app.ui && app.ui.reset_list && app.ui.reset_list();
        app.multiDeck.render_tabs();
    },

    // Count of distinct cards in deckIdx's main deck that exceed owned copies
    // across all decks combined. Matches the orange alert logic in the card display.
    getConflictCount: function(deckIdx) {
        var count = 0;
        if (deckIdx === app.multiDeck.activeIdx) {
            app.data.cards.find({ indeck: { '$gt': 0 } }).forEach(function(card) {
                var owned = card.owned_copies != null ? card.owned_copies : 999;
                if (app.multiDeck.getCombinedQty(card.code) > owned) count++;
            });
        } else {
            var d = app.multiDeck.decks[deckIdx];
            Object.keys(d.slots || {}).forEach(function(code) {
                if (!d.slots[code]) return;
                var card = app.data.cards.findById(code);
                if (!card) return;
                var owned = card.owned_copies != null ? card.owned_copies : 999;
                if (app.multiDeck.getCombinedQty(code) > owned) count++;
            });
        }
        return count;
    },

    // Sum of a card's quantity across every open deck.
    // Reads live from ForerunnerDB for the active deck so edits show immediately.
    getCombinedQty: function(code) {
        var total = 0;
        app.multiDeck.decks.forEach(function(d, i) {
            if (i === app.multiDeck.activeIdx) {
                var card = app.data.cards.findById(code);
                total += (card ? (card.indeck || 0) : 0);
            } else {
                total += (d.slots[code] || 0);
            }
        });
        return total;
    },

    // AJAX-save every deck that has cards, then redirect to the deck list.
    saveAll: function() {
        app.multiDeck.captureActive();

        var btn = jQuery('#save_form button[type=submit]').first();
        btn.prop('disabled', true).text('Saving…');

        var promises = app.multiDeck.decks.map(function(d) {
            var content = JSON.stringify({ main: d.slots || {}, side: d.sideslots || {} });
            return jQuery.ajax({
                url: Routing.generate('deck_save_ajax'),
                method: 'POST',
                data: {
                    id: d.id || '',
                    name: d.name || 'Untitled Deck',
                    content: content,
                    description: d.description_md || '',
                    tags: d.tags || ''
                }
            }).then(function(resp) {
                if (resp && resp.success) d.id = resp.id;
                return resp;
            });
        });

        jQuery.when.apply(jQuery, promises).then(function() {
            window.location.href = Routing.generate('decks_list');
        }, function(xhr) {
            var msg = 'Save failed.';
            try { var r = JSON.parse(xhr.responseText); msg = r.error || msg; } catch(e) {}
            alert(msg);
            btn.prop('disabled', false).text('Save All');
        });
    },

    // Rebuild the deck selector list above the deck display.
    render_tabs: function() {
        var container = jQuery('#multi-deck-tabs');
        if (!container.length) return;

        function heroCards(d, i) {
            var heroes = [];
            if (i === app.multiDeck.activeIdx) {
                app.data.cards.find({ type_code: 'hero', indeck: { '$gt': 0 } }).forEach(function(card) {
                    heroes.push(card);
                });
            } else {
                Object.keys(d.slots || {}).forEach(function(code) {
                    if (d.slots[code] > 0) {
                        var card = app.data.cards.findById(code);
                        if (card && card.type_code === 'hero') heroes.push(card);
                    }
                });
            }
            heroes.sort(function(a, b) { return a.code < b.code ? -1 : 1; });
            return heroes;
        }

        var html = '<div class="multi-deck-browser-bar">';
        html += '<ul class="multi-deck-browser-tabs">';

        app.multiDeck.decks.forEach(function(d, i) {
            var isActive = (i === app.multiDeck.activeIdx);
            var label = jQuery('<div/>').text(d.name || ('Deck ' + (i + 1))).html();
            var liClass = 'multi-deck-browser-tab' + (isActive ? ' active' : ' multi-deck-tab');
            var attrs = isActive ? '' : ' data-idx="' + i + '" role="button"';
            var conflicts = app.multiDeck.decks.length > 1 ? app.multiDeck.getConflictCount(i) : 0;
            var badge = conflicts > 0
                ? '<span class="multi-deck-conflict-badge">' + conflicts + '</span>'
                : '';
            var heroes = heroCards(d, i);
            var heroHtml = '<div class="multi-deck-tab-heroes">';
            if (heroes.length) {
                heroHtml += heroes.map(function(h) {
                    return '<span class="multi-deck-tab-hero-name fg-' + h.sphere_code + '">'
                        + jQuery('<div/>').text(h.name).html()
                        + '</span>';
                }).join('');
            } else {
                heroHtml += '<span class="multi-deck-tab-no-heroes">No heroes yet</span>';
            }
            heroHtml += '</div>';
            var closeBtn = app.multiDeck.decks.length > 1
                ? '<button class="multi-deck-close-tab" data-idx="' + i + '" type="button" title="Close">×</button>'
                : '';
            html += '<li class="' + liClass + '"' + attrs + '>';
            html += '<span class="multi-deck-tab-label">' + label + '</span>' + closeBtn;
            html += heroHtml + badge;
            html += '</li>';
        });

        html += '</ul>';
        html += '<button class="js-add-deck multi-deck-add-btn" type="button"'
            + ' data-toggle="popover" data-trigger="hover" data-placement="bottom"'
            + ' data-title="Building multiple decks"'
            + ' data-content="Add decks to build them side by side against your collection. The orange warning fires when combined copies across all open decks exceed what you own. Each deck is saved individually.">'
            + '<span class="fa fa-plus"></span></button>';
        html += '<button class="js-load-deck multi-deck-add-btn" type="button"'
            + ' data-toggle="popover" data-trigger="hover" data-placement="bottom"'
            + ' data-title="Load an existing deck"'
            + ' data-content="Open one of your saved decks in the builder to compare or edit it alongside other decks.">'
            + '<span class="fa fa-folder-open"></span></button>';
        html += '</div>';

        container.html(html);
        container.find('.js-add-deck, .js-load-deck').popover({ html: false, container: 'body' });
    },

    openLoadModal: function() {
        var modal = jQuery('#multiDeckLoadModal').modal('show');
        var tbody = modal.find('#multiDeckLoadList').empty()
            .append('<tr><td class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></td></tr>');

        jQuery.ajax(Routing.generate('api_private_my_decks'), {
            type: 'GET',
            dataType: 'json',
            success: function(decks) {
                tbody.empty();
                if (!decks || !decks.length) {
                    tbody.append('<tr><td>No decks found.</td></tr>');
                    return;
                }
                decks.forEach(function(deck) {
                    if (deck.is_published) return;

                    var alreadyOpen = app.multiDeck.decks.some(function(d) {
                        return d.id && d.id == deck.id;
                    });

                    var tr = jQuery('<tr/>');
                    var tdHero = jQuery('<td class="decklist-hero-image hidden-xs"/>').appendTo(tr);
                    jQuery.each(deck.heroes || {}, function(heroCode) {
                        var heroCard = app.data.cards.findById(heroCode);
                        if (!heroCard) return;
                        jQuery('<div class="decklist-hero"/>')
                            .addClass('border-light-' + heroCard.sphere_code)
                            .append('<div class="hero-thumbnail card-thumbnail-4x card-thumbnail-hero" style="background-image:url(\'/bundles/cards/' + heroCard.code + '.png\')"></div>')
                            .appendTo(tdHero);
                    });

                    var label = deck.name + ' ' + (deck.version || '');
                    var tdName = jQuery('<td/>').appendTo(tr);
                    jQuery('<div/>').text(label.trim()).appendTo(tdName);

                    var tdAction = jQuery('<td class="deck-selection-actions text-right"/>').appendTo(tr);
                    if (alreadyOpen) {
                        jQuery('<span class="text-muted small">Open</span>').appendTo(tdAction);
                    } else {
                        jQuery('<a href="" class="btn btn-xs btn-default" title="Add to builder"><span class="fa fa-check fa-fw text-success"></span></a>')
                            .appendTo(tdAction)
                            .on('click', function(e) {
                                e.preventDefault();
                                var route = deck.is_published
                                    ? Routing.generate('api_decklist', { decklist_id: deck.id })
                                    : Routing.generate('api_private_load_deck', { id: deck.id });
                                jQuery.ajax(route, {
                                    type: 'GET',
                                    dataType: 'json',
                                    cache: false,
                                    success: function(deckData) {
                                        jQuery('#multiDeckLoadModal').modal('hide');
                                        app.multiDeck.addExistingDeck(deckData);
                                    }
                                });
                            });
                    }

                    tbody.append(tr);
                });
            }
        });
    },

    addExistingDeck: function(deckData) {
        app.multiDeck.captureActive();
        var deckName = deckData.name || 'Loaded Deck';
        var newDeck = {
            id: deckData.id || null,
            name: deckName,
            description_md: deckData.description_md || '',
            tags: deckData.tags || '',
            slots: deckData.slots || {},
            sideslots: deckData.sideslots || {},
            history: deckData.history || [],
            uiState: null
        };
        app.multiDeck.decks.push(newDeck);
        app.multiDeck.activeIdx = app.multiDeck.decks.length - 1;
        app.deck.setMeta(newDeck);
        app.deck.set_slots(newDeck.slots, newDeck.sideslots);
        app.deck_history && app.deck_history.reset(newDeck.history);
        jQuery('input.decklist-name').val(deckName);
        jQuery('#deck-save-id').val(newDeck.id || '');
        jQuery('textarea[name="description_"]').val(newDeck.description_md || '');
        jQuery('input[name="tags_"]').val(newDeck.tags || '');
        app.ui && app.ui.apply_default_filters && app.ui.apply_default_filters();
        jQuery('#save_form button[type=submit]').first().text('Save All');
        app.ui && app.ui.refresh_deck && app.ui.refresh_deck();
        app.ui && app.ui.reset_list && app.ui.reset_list();
        app.multiDeck.render_tabs();
    },

    pendingCloseIdx: null,

    openCloseModal: function(idx) {
        app.multiDeck.pendingCloseIdx = idx;
        var d = app.multiDeck.decks[idx];
        jQuery('#multiDeckCloseMessage').text('"' + (d.name || 'This deck') + '" has unsaved changes.');
        jQuery('#multiDeckCloseModal').modal('show');
    },

    confirmClose: function(save) {
        var idx = app.multiDeck.pendingCloseIdx;
        if (idx === null) return;
        jQuery('#multiDeckCloseModal').modal('hide');

        if (save) {
            if (idx === app.multiDeck.activeIdx) {
                app.multiDeck.captureActive();
            }
            var d = app.multiDeck.decks[idx];
            if (!d.slots || Object.keys(d.slots).length === 0) {
                app.multiDeck.removeTab(idx);
                return;
            }
            jQuery.ajax({
                url: Routing.generate('deck_save_ajax'),
                method: 'POST',
                data: {
                    id: d.id || '',
                    name: d.name || 'Untitled Deck',
                    content: JSON.stringify({ main: d.slots || {}, side: d.sideslots || {} }),
                    description: d.description_md || '',
                    tags: d.tags || ''
                },
                success: function(resp) {
                    if (resp && resp.success) d.id = resp.id;
                    app.multiDeck.removeTab(idx);
                },
                error: function(xhr) {
                    var msg = 'Save failed. The tab was not closed.';
                    try { var r = JSON.parse(xhr.responseText); msg = r.error || msg; } catch(e) {}
                    alert(msg);
                }
            });
        } else {
            app.multiDeck.removeTab(idx);
        }
    },

    removeTab: function(idx) {
        var wasActive = (idx === app.multiDeck.activeIdx);
        app.multiDeck.decks.splice(idx, 1);

        var newIdx = wasActive
            ? Math.min(idx, app.multiDeck.decks.length - 1)
            : idx < app.multiDeck.activeIdx ? app.multiDeck.activeIdx - 1 : app.multiDeck.activeIdx;
        app.multiDeck.activeIdx = newIdx;

        if (wasActive) {
            var d = app.multiDeck.decks[newIdx];
            app.deck.setMeta(d);
            app.deck.set_slots(d.slots, d.sideslots);
            app.deck_history && app.deck_history.reset(d.history || []);
            jQuery('input.decklist-name').val(d.name || '');
            jQuery('#deck-save-id').val(d.id || '');
            jQuery('textarea[name="description_"]').val(d.description_md || '');
            jQuery('input[name="tags_"]').val(d.tags || '');
            app.ui && app.ui.restore_filter_state && app.ui.restore_filter_state(d.uiState || null);
            app.ui && app.ui.refresh_deck && app.ui.refresh_deck();
            app.ui && app.ui.reset_list && app.ui.reset_list();
        }

        if (app.multiDeck.decks.length === 1) {
            jQuery('#save_form button[type=submit]').first().text('Save');
        }

        app.multiDeck.render_tabs();
    }
};
