(function app_deck(deck, $) {

    var date_creation;
    var date_update;
    var description_md;
    var id;
    var name;
    var tags;
    var unsaved;
    var user_id;

    var header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>');
    var card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> fg-<%= card.sphere_code %>"></span> <a href="<%= card.url %>" class="card card-tip fg-<%= card.sphere_code %>" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>');
    var layouts = {};
    var displayFullPackInfo = false;

    /*
     * Templates for the different deck layouts, see deck.get_layout_data
     */

    layouts.type = {};
    layouts.type[1] = _.template('<div class="deck-content"><%= meta %><%= heroes %><%= allies %><%= attachments %><%= events %><%= sidequests %><%= treasures %></div>');
    layouts.type[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-12"><%= meta %></div></div><div class="row"><div class="col-sm-12"><%= heroes %></div></div><div class="row"><div class="col-sm-6"><%= allies %></div><div class="col-sm-6"><%= attachments %><%= events %><%= sidequests %><%= treasures %></div></div></div>');
    layouts.type[3] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-4"><%= meta %><%= heroes %></div><div class="col-sm-4"><%= allies %></div><div class="col-sm-4"><%= attachments %><%= events %><%= sidequests %><%= treasures %></div></div></div>');

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
        displayFullPackInfo = true;
        app.ui.refresh_deck();
    });


    deck.problem_labels = {
        too_many_heroes: "Contains too many Heroes",
        too_few_heroes: "Contains too few Heroes",
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

        if (app.data.isLoaded) {
            deck.set_slots(data.slots, data.sideslots);
        } else {
            $(document).on('data.app', function () {
                deck.set_slots(data.slots, data.sideslots);
            });
        }
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
    deck.get_description_md = function() {
        return description_md;
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
                if (hero.sphere_code == 'lore') {
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
        var quantities = _.pluck(cards, is_sideboard ? 'insideboard' : 'indeck');
        return _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
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
        }
    };

    deck.get_side_layout_data = function(options) {
        var data = {
        };

        var packs = deck.get_included_packs(true);

        if (!packs.length) {
            return null;
        }

        var title = $('<h4 style="font-weight: bold">Sideboard</h4>');
        deck.update_layout_section(data, 'meta', title);

        if (options.sort == 'type') {
            deck.update_layout_section(data, 'heroes', deck.get_layout_data_one_section('type_code', 'hero', 'type_name', true));
            deck.update_layout_section(data, 'allies', deck.get_layout_data_one_section('type_code', 'ally', 'type_name', true));
            deck.update_layout_section(data, 'attachments', deck.get_layout_data_one_section('type_code', 'attachment', 'type_name', true));
            deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name', true));
            deck.update_layout_section(data, 'sidequests', deck.get_layout_data_one_section('type_code', 'player-side-quest', 'type_name', true));
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
            title = $('<h4 style="font-weight: bold">' + deck.get_name() + '</h4>').attr('title', deck.get_name());
        } else {
            title = $('<h4 style="font-weight: bold">Main Deck</h4>');
        }


        var threat = $('<div>Starting Threat: <b>' + deck.get_starting_threat() + '</b></div>')

        var text = [herocount, herocount == 1 ? ' Hero, ': ' Heroes, ', drawcount, drawcount == 1 ? ' Card': ' Cards' ].join(' ');
        var sizeinfo = $('<div class="cardcount">' + text + '</div>');

        if (drawcount < 50 || herocount == 0 || deck.get_hero_deck_size(true) > 3) {
            sizeinfo.addClass('text-danger');
        }

        var packs = deck.get_included_packs();

        var packinfo;
        if (displayFullPackInfo) {
            packinfo = $('<div>Packs: ' + _.map(packs, function (pack) { return pack.name + (pack.quantity > 1 ? ' (' + pack.quantity + ')' : ''); }).join(', ') + '</div>');
        } else {
            packinfo = $('<div class="latestpack">Cards up to <i>' + (deck.get_lastest_pack().name || '-') + '</i></div>');

            if (packs.length) {
                $('<small><i style="cursor: pointer; padding-left: 5px;" class="fa fa-plus-square-o expand-packs"></i></small>').appendTo(packinfo);
            }
        }

        deck.update_layout_section(data, 'meta', title);
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
                deck.update_layout_section(data, 'treasures', deck.get_layout_data_one_section('type_code', 'treasure', 'type_name'));
            } else {
                deck.update_layout_section(data, 'allies', '');
                deck.update_layout_section(data, 'attachments', '');
                deck.update_layout_section(data, 'events', '');
                deck.update_layout_section(data, 'sidequests', '');
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

        cards.forEach(function(card) {
            if (card.type_code == 'hero' && sortKey == 'type_code' && !is_sideboard) {
                var div = $('<div class="deck-hero"/>')
                    .append('<div class="hero-thumbnail card-thumbnail-2x card-thumbnail-hero" style="background-image:url(\'/bundles/cards/' + card.code + '.png\')"></div>')
                    .append($(card_line_tpl({ card: card })));

                if (!deck.i_have_this_card(card)) {
                    div.append(' <i class="fa fa-exclamation-triangle not-in-collection" title="This card is not in my collection"></i>');
                }

                div.appendTo(section);
            } else {
                var tpl = $(card_line_tpl({ card: card }));

                var div = $('<div />').append(tpl).prepend(sortKey != 'type_code' || card.type_code != 'hero' ? card[key] + 'x ' : '').appendTo(section);

                if (card.is_unique && card.type_code != 'hero') {
                    div.find('a').css('font-weight', 'bold');
                }

                if (!deck.can_include_card(card)) {
                    div.addClass('invalid-card');
                }

                if (!deck.i_have_this_card(card)) {
                    div.append(' <i class="fa fa-exclamation-triangle not-in-collection" title="This card is not in my collection"></i>');
                }

                if (sortKey == 'pack_code') {
                    div.append(' <small class="text-muted" style="padding-left: 2px">(#' + card.position + ')</small>');
                }
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

    /**
     * @memberOf deck
     */
    deck.get_problem = function() {
        // exactly 7 plots
        var herocount = deck.get_hero_deck_size(true);
        if (herocount > 3) {
            return 'too_many_heroes';
        }

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
        if (decksize < 40) {
            return 'too_few_cards';
        } else if (decksize < 50) {
            return 'invalid_for_tournament_play';
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
                    var qty = line.ignore('a, span, small').text().trim().replace(/x.*/, 'x');
                    var card = app.data.cards.findById(line.find('a.card').data('code'));

                    if (card) {
                        var str = qty + ' [url=http://ringsdb.com/card/' + card.code + ']' + card.name + '[/url] [i](' + card.pack_name + ')[/i]';
                        lines.push(str.trim());
                    }
                });
                lines.push('');
            });


            var count = content.find('.cardcount').text();
            if (count) {
                lines.push(count);
            }

            var latestpack = content.find('.latestpack').text();
            if (latestpack) {
                lines.push(latestpack);
            }
            lines.push('');
        });

        if (app.user.params.decklist_id) {
            lines.push('Decklist [url='+location.href+']build and published on RingsDB[/url].');
        } else {
            lines.push('Deck built on [url=http://ringsdb.com]RingsDB[/url].');
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
                    var qty = line.ignore('a, span, small').text().trim().replace(/x.*/, 'x');
                    var card = app.data.cards.findById(line.find('a.card').data('code'));

                    if (card) {
                        lines.push('* ' + qty + ' [' + card.name + '](http://ringsdb.com/card/' + card.code + ') _(' + card.pack_name + ')_');
                    }
                });

                lines.push('');
            });


            var count = content.find('.cardcount').text();
            if (count) {
                lines.push(count);
            }

            var latestpack = content.find('.latestpack').text();
            if (latestpack) {
                lines.push(latestpack);
            }
            lines.push('');
        });

        if (app.user.params.decklist_id) {
            lines.push('Decklist [build and published on RingsDB]('+location.href+').');
        } else {
            lines.push('Deck built on [RingsDB](http://ringsdb.com).');
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

                    if (card) {
                        lines.push($(line).text().trim() + ' (' + card.pack_name + ')');
                    }
                });
                lines.push('');
            });

            var count = content.find('.cardcount').text();
            if (count) {
                lines.push(count);
            }

            var latestpack = content.find('.latestpack').text();
            if (latestpack) {
                lines.push(latestpack);
            }
            lines.push('');
        });

        if (app.user.params.decklist_id) {
            lines.push('Decklist built and published on ' + location.href);
        } else {
            lines.push('Deck built on http://ringsdb.com.');
        }
        return lines;
    };
})(app.deck = {}, jQuery);
