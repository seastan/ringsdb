(function app_deck(deck, $) {

    var date_creation;
    var date_update;
    var description_md;
    var id;
    var name;
    var tags;
    var unsaved;
    var user_id;
    var problem_labels = {
        too_many_heroes: "Contains too many Heroes",
        too_few_heroes: "Contains too few Heroes",
        duplicated_unique_heroes: "More than one hero with the same unique name",
        too_few_cards: "Contains too few cards",
        invalid_cards: "Contains forbidden cards"
    };
    var header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>');
    var card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> fg-<%= card.sphere_code %>"></span> <a href="<%= card.url %>" class="card card-tip fg-<%= card.sphere_code %>" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>');
    var layouts = {};

    /*
     * Templates for the different deck layouts, see deck.get_layout_data
     */

    layouts.type = {};
    layouts.type[1] = _.template('<div class="deck-content"><%= meta %><%= heroes %><%= allies %><%= attachments %><%= events %><%= sidequests %></div>');
    layouts.type[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-12"><%= meta %></div></div><div class="row"><div class="col-sm-12"><%= heroes %></div></div><div class="row"><div class="col-sm-6"><%= allies %></div><div class="col-sm-6"><%= attachments %><%= events %><%= sidequests %></div></div></div>');
    layouts.type[3] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-4"><%= meta %><%= heroes %></div><div class="col-sm-4"><%= allies %></div><div class="col-sm-4"><%= attachments %><%= events %><%= sidequests %></div></div></div>');

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

    /**
     * @memberOf deck
     */
    deck.init = function init(data) {
        date_creation = data.date_creation;
        date_update = data.date_update;
        description_md = data.description_md;
        id = data.id;
        name = data.name;
        tags = data.tags;
        unsaved = data.unsaved;
        user_id = data.user_id;

        if (app.data.isLoaded) {
            deck.set_slots(data.slots);
        } else {
            $(document).on('data.app', function () {
                deck.set_slots(data.slots);
            });
        }
    };

    deck.set_slots = function set_slots(slots) {
        app.data.cards.update({}, {
            indeck: 0
        });

        for (code in slots) {
            if (slots.hasOwnProperty(code)) {
                app.data.cards.updateById(code, { indeck: slots[code] });
            }
        }
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_id = function get_id() {
        return id;
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_name = function get_name() {
        return name;
    };

    /**
     * @memberOf deck
     * @returns string
     */
    deck.get_description_md = function get_description_md() {
        return description_md;
    };

    /**
     * @memberOf deck
     */
    deck.get_heroes = function get_heroes() {
        return deck.get_cards(null, {
            type_code: 'hero'
        });
    };

    /**
     * @memberOf deck
     */
    deck.get_cards = function get_cards(sort, query) {
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

    /**
     * @memberOf deck
     */
    deck.get_draw_deck = function get_draw_deck(sort) {
        return deck.get_cards(sort, {
            type_code: {
                '$nin': ['hero']
            }
        });
    };

    /**
     * @memberOf deck
     */
    deck.get_draw_deck_size = function get_draw_deck_size(sort) {
        var draw_deck = deck.get_draw_deck(sort);
        return deck.get_nb_cards(draw_deck);
    };

    /**
     * @memberOf deck
     */
    deck.get_hero_deck = function get_hero_deck(primarySpheresOnly, sort) {
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
    deck.get_hero_deck_size = function get_hero_deck_size(primarySpheres, sort) {
        var hero_deck = deck.get_hero_deck(primarySpheres, sort);
        return deck.get_nb_cards(hero_deck);
    };

    deck.get_starting_threat = function() {
        var hero_deck = deck.get_hero_deck();
        var threat = _.pluck(hero_deck, 'threat');
        return _.reduce(threat, function(memo, num) { return memo + num; }, 0);
    };

    deck.get_nb_cards = function get_nb_cards(cards) {
        if (!cards) {
            cards = deck.get_cards();
        }
        var quantities = _.pluck(cards, 'indeck');
        return _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
    };

    /**
     * @memberOf deck
     */
    deck.get_included_packs = function() {
        var cards = deck.get_cards();
        var nb_packs = {};

        cards.forEach(function(card) {
            nb_packs[card.pack_code] = Math.max(nb_packs[card.pack_code] || 0, card.indeck / card.quantity);
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

    deck.get_lastest_pack = function() {
        var packs = deck.get_included_packs();
        return packs[packs.length - 1] || {};
    };

    /**
     * @memberOf deck
     */
    deck.display = function display(container, options) {
        options = _.extend({ sort: 'type', cols: 2 }, options);

        var layout_data = deck.get_layout_data(options);
        var deck_content = layouts[options.sort][options.cols](layout_data);

        $(container).removeClass('deck-loading').empty();
        $(container).append(deck_content);
    };

    deck.get_layout_data = function(options) {
        var data = {
        };

        var problem = deck.get_problem();
        var herocount = deck.get_hero_deck_size();
        var drawcount = deck.get_draw_deck_size();

        var title = $('<h4 style="font-weight: bold">Main Deck</h4>');
        var threat = $('<div>Starting Threat: <b>' + deck.get_starting_threat() + '</b></div>')

        var text = [herocount, herocount == 1 ? ' Hero, ': ' Heroes, ', drawcount, drawcount == 1 ? ' Card': ' Cards' ].join(' ');
        var sizeinfo = $('<div id="cardcount">' + text + '</div>');

        if (drawcount < 50 || herocount == 0 || deck.get_hero_deck_size(true) > 3) {
            sizeinfo.addClass('text-danger');
        }

        var packinfo = $('<div id="latestpack">Cards up to <i>' + (deck.get_lastest_pack().name || '-') + '</i></div>');


        deck.update_layout_section(data, 'meta', title);
        deck.update_layout_section(data, 'meta', threat);
        deck.update_layout_section(data, 'meta', sizeinfo);
        deck.update_layout_section(data, 'meta', packinfo);

        if (problem) {
            var probleminfo = $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> ' + problem_labels[problem] + '</div>');
            deck.update_layout_section(data, 'meta', probleminfo);
        }

        if (options.sort == 'type') {
            deck.update_layout_section(data, 'heroes', deck.get_layout_data_one_section('type_code', 'hero', 'type_name'));
            deck.update_layout_section(data, 'allies', deck.get_layout_data_one_section('type_code', 'ally', 'type_name'));
            deck.update_layout_section(data, 'attachments', deck.get_layout_data_one_section('type_code', 'attachment', 'type_name'));
            deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name'));
            deck.update_layout_section(data, 'sidequests', deck.get_layout_data_one_section('type_code', 'player-side-quest', 'type_name'));
        }

        if (options.sort == 'position') {
            var packs = deck.get_included_packs();

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

    deck.update_layout_section = function update_layout_section(data, section, element) {
        if (!data[section]) {
            data[section] = '';
        }

        if (!element) {
            return '';
        }

        data[section] += element[0].outerHTML;
    };

    deck.get_layout_data_one_section = function(sortKey, sortValue, displayLabel) {
        var section = $('<div />');
        var query = {};

        if (sortValue) {
            query[sortKey] = sortValue;
        }

        var cards;

        if (sortKey == 'type_code' || sortKey == 'name') {
            cards = deck.get_cards({ name: 1 }, query);
        } else if (sortKey == 'pack_code') {
            cards = deck.get_cards({ position: 1 }, query);
        } else {
            cards = deck.get_cards({ type_code: 1, code: 1 }, query);
        }

        if (!cards.length) {
            return null;
        }

        $(header_tpl({ code: sortValue, name: cards[0][displayLabel] || 'Cards', quantity: deck.get_nb_cards(cards) })).appendTo(section);

        cards.forEach(function(card) {
            if (card.type_code == 'hero' && sortKey == 'type_code') {
                $('<div class="deck-hero"/>')
                    .append('<div class="hero-thumbnail card-thumbnail-2x card-thumbnail-hero" style="background-image:url(\'/bundles/cards/' + card.code + '.png\'"></div>')
                    .append($(card_line_tpl({ card: card })))
                    .appendTo(section);
            } else {
                var tpl = $(card_line_tpl({ card: card }));

                var div = $('<div />').append(tpl).prepend(sortKey != 'type_code' || card.type_code != 'hero' ? card.indeck + 'x ' : '').appendTo(section);

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
        }

        return section;
    };

    /**
     * @memberOf deck
     * @return boolean true if at least one other card quantity was updated
     */
    deck.set_card_copies = function set_card_copies(card_code, nb_copies) {
        var card = app.data.cards.findById(card_code);
        if (!card) {
            return false;
        }

        var updated_other_card = false;

        // card-specific rules
        // Reserved for future features
        /*
        switch (card.type_code) {
            case 'agenda':
                app.data.cards.update({
                    type_code: 'agenda'
                }, {
                    indeck: 0
                });
                updated_other_card = true;
                break;
        }
        */

        app.data.cards.updateById(card_code, {
            indeck: nb_copies
        });
        app.deck_history && app.deck_history.notify_change();

        return updated_other_card;
    };

    /**
     * @memberOf deck
     */
    deck.get_content = function get_content() {
        var cards = deck.get_cards();
        var content = {};
        cards.forEach(function(card) {
            content[card.code] = card.indeck;
        });
        return content;
    };

    /**
     * @memberOf deck
     */
    deck.get_json = function get_json() {
        return JSON.stringify(deck.get_content());
    };

    /**
     * @memberOf deck
     */
    deck.get_export = function get_export(format) {

    };

    /**
     * @memberOf deck
     */
    deck.get_problem = function get_problem() {
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
        if (deck.get_draw_deck_size() < 50) {
            return 'too_few_cards';
        }

        // no invalid card
        if (deck.get_invalid_cards().length > 0) {
            return 'invalid_cards';
        }
    };

    /**
     * @memberOf deck
     * @returns
     */
    deck.get_heroes_spheres_code = function() {
        var heroes = deck.get_hero_deck();
        return _.uniq(_.pluck(heroes, 'sphere_code'));
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

    deck.export_bbcode = function() {
        $('#export-deck').html(deck.build_bbcode().join("\n"));
        $('#exportModal').modal('show');
    };

    deck.build_bbcode = function() {
        var lines = [];

        lines.push("[b]" + deck.get_name() + "[/b]");

        $('#deck-content h5:visible').each(function(i, type) {
            lines.push("");
            lines.push("[b]" + $(type).text().trim() + "[/b]");

            $(type).siblings().each(function(j, line) {
                var line = $(line);
                var qty = line.ignore("a, span, small").text().trim().replace(/x.*/, "x");
                var card = app.data.cards.findById(line.find('a.card').data('code'));

                if (card) {
                    lines.push(qty + ' [url=http://ringsdb.com/card/' + card.code + ']' + card.name + '[/url] [i](' + card.pack_name + ")[/i]");
                }
            });
        });

        lines.push("");
        lines.push($('#cardcount').text());
        lines.push($('#latestpack').text());
        lines.push("");

        if (app.user.params.decklist_id) {
            lines.push("Decklist [url="+location.href+"]build and published on RingsDB[/url].");
        } else {
            lines.push("Deck built on [url=http://ringsdb.com]RingsDB[/url].");
        }

        return lines;
    };

    deck.export_markdown = function() {
        $('#export-deck').html(deck.build_markdown().join("\n"));
        $('#exportModal').modal('show');
    };

    deck.build_markdown = function() {
        var lines = [];

        lines.push("## " + deck.get_name());

        $('#deck-content h5:visible').each(function(i, type) {
            lines.push("");
            lines.push("###" + $(type).text().trim());

            $(type).siblings().each(function(j, line) {
                var line = $(line);
                var qty = line.ignore("a, span, small").text().trim().replace(/x.*/, "x");
                var card = app.data.cards.findById(line.find('a.card').data('code'));

                if (card) {
                    lines.push('* '+ qty + ' [' + card.name + '](http://ringsdb.com/card/' + card.code + ') _(' + card.pack_name + ")_");
                }
            });
        });

        lines.push("");
        lines.push($('#cardcount').text() + "  ");
        lines.push($('#latestpack').text() + "  ");
        lines.push("");

        if (app.user.params.decklist_id) {
            lines.push("Decklist [build and published on RingsDB]("+location.href+").");
        } else {
            lines.push("Deck built on [RingsDB](http://ringsdb.com).");
        }
        return lines;
    };

    deck.export_plaintext = function() {
        $('#export-deck').html(deck.build_plaintext().join("\n"));
        $('#exportModal').modal('show');
    };

    deck.build_plaintext = function() {
        var lines = [];

        lines.push(deck.get_name());

        $('#deck-content h5:visible').each(function(i, type) {
            lines.push("");
            lines.push($(type).text().trim());

            $(type).siblings().each(function(j, line) {
                var line = $(line);
                var card = app.data.cards.findById(line.find('a.card').data('code'));

                if (card) {
                    lines.push($(line).text().trim() + ' (' + card.pack_name + ')');
                }
            });
        });

        lines.push("");
        lines.push($('#cardcount').text());
        lines.push($('#latestpack').text());
        lines.push("");

        if (app.user.params.decklist_id) {
            lines.push("Decklist built and published on " + location.href);
        } else {
            lines.push("Deck built on http://ringsdb.com.");
        }
        return lines;
    };
})(app.deck = {}, jQuery);
