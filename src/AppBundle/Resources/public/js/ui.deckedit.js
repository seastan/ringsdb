(function ui_deck(ui, $) {

    ui.deckedit = true;

    var DisplayColumnsTpl = '';
    var SortKey = 'type_code';
    var SortOrder = 1;
    var CardDivs = [[], [], []];
    var Config = null;
    var DisplayOptions;

    /**
     * reads ui configuration from localStorage
     * @memberOf ui
     */
    ui.read_config_from_storage = function() {
        if (localStorage) {
            var stored = localStorage.getItem('ui.deck.config');

            if (stored) {
                Config = JSON.parse(stored);
            }
        }

        Config = _.extend({
            'show-unusable': false,
            'show-only-deck': false,
            'compact-mode': false,
            'display-column': 1,
            'show-suggestions': 0,
            'buttons-behavior': 'cumulative'
        }, Config || {});
    };

    /**
     * write ui configuration to localStorage
     * @memberOf ui
     */
    ui.write_config_to_storage = function() {
        if (localStorage) {
            localStorage.setItem('ui.deck.config', JSON.stringify(Config));
        }
    };

    /**
     * inits the state of config buttons
     * @memberOf ui
     */
    ui.init_config_buttons = function() {
        // radio
        ['display-column', 'core-set', 'show-suggestions', 'buttons-behavior'].forEach(function (radio) {
            $('input[name=' + radio + '][value=' + Config[radio] + ']').prop('checked', true);
        });

        // checkbox
        ['show-unusable', 'show-only-deck', 'compact-mode'].forEach(function (checkbox) {
            if (Config[checkbox]) $('input[name=' + checkbox + ']').prop('checked', true);
        })
    };

    /**
     * sets the maxqty of each card
     * @memberOf ui
     */
    ui.set_max_qty = function() {
        app.data.cards.find().forEach(function (record) {
            var max_qty = Math.min(3, record.deck_limit);

            if (record.pack_code == 'Core') {
                max_qty = Math.min(max_qty, record.quantity * Config['core-set']);
            } else {
                max_qty = Math.min(max_qty, record.quantity);
            }

            app.data.cards.updateById(record.code, {
                maxqty: max_qty
            });
        });
    };

    ui.set_cores_qty = function() {
        var cores = 1;
        if (app.user.data.owned_packs) {
            if (app.user.data.owned_packs.match(/1-2/)) {
                cores++;
            }
            if (app.user.data.owned_packs.match(/1-3/)) {
                cores++;
            }
        } else {
            cores = 3;
        }

        Config['core-set'] = cores;
    };

    /**
     * builds the sphere selector
     * @memberOf ui
     */
    ui.build_sphere_selector = function() {
        var filter = $('[data-filter="sphere_code"]').empty();

        var sphere_codes = app.data.cards.distinct('sphere_code').sort();

        sphere_codes.splice(sphere_codes.indexOf('neutral'), 1);
        sphere_codes.push('neutral');
        sphere_codes.splice(sphere_codes.indexOf('baggins'), 1);
        sphere_codes.push('baggins');
        sphere_codes.splice(sphere_codes.indexOf('fellowship'), 1);
        sphere_codes.push('fellowship');

        sphere_codes.forEach(function(sphere_code) {
            var example = app.data.cards.find({ 'sphere_code': sphere_code })[0];

            var label = $('<label class="btn btn-default btn-sm" data-code="' + sphere_code + '" title="' + example.sphere_name + '"></label>');

            $('<input type="checkbox" name="' + sphere_code + '"><span class="icon-' + sphere_code + '"></span>')
                .tooltip({ container: 'body' })
                .appendTo(label);

            label.appendTo(filter);
        });

        filter.button();
    };

    /**
     * builds the type selector
     * @memberOf ui
     */
    ui.build_type_selector = function() {
        var filter = $('[data-filter="type_code"]').empty();

        var type_codes = app.data.cards.distinct('type_code');
        type_codes.splice(type_codes.indexOf('contract'), 1);
        type_codes.unshift('contract');
        type_codes.splice(type_codes.indexOf('treasure'), 1);
        type_codes.unshift('treasure');
        type_codes.splice(type_codes.indexOf('player-side-quest'), 1);
        type_codes.unshift('player-side-quest');
        type_codes.splice(type_codes.indexOf('event'), 1);
        type_codes.unshift('event');
        type_codes.splice(type_codes.indexOf('attachment'), 1);
        type_codes.unshift('attachment');
        type_codes.splice(type_codes.indexOf('ally'), 1);
        type_codes.unshift('ally');
        type_codes.splice(type_codes.indexOf('hero'), 1);
        type_codes.unshift('hero');

        type_codes.forEach(function(type_code) {
            var example = app.data.cards.find({ 'type_code': type_code })[0];

            var label = $('<label class="btn btn-default btn-sm" data-code="' + type_code + '" title="' + example.type_name + '"></label>');

            $('<input type="checkbox" name="' + type_code + '"><span class="icon-' + type_code + '"></span>')
                .tooltip({ container: 'body' })
                .appendTo(label);

            label.appendTo(filter);
        });

        filter.button();
    };

    /**
     * builds the pack selector
     * @memberOf ui
     */
    ui.build_pack_selector = function() {
        $('[data-filter="pack_code"]').empty();

        app.data.packs.find({
            name: {
                '$exists': true
            }
        }, {
            $orderBy: {
                cycle_position: 1,
                position: 1
            }
        }).forEach(function(record) {
            var checked = record.owned;

            // if pack used by cards in deck, check pack
            var cards = app.data.cards.find({
                pack_code: record.code,
                '$or': [
                    { indeck: { '$gt': 0 } },
                    { insideboard: { '$gt': 0 }
                }]
            });

            if (cards.length) {
                checked = true;
            }

            $('<li><a href=""><label><input type="checkbox" name="' + record.code + '"' + (checked ? ' checked="checked"' : '') + '>' + record.name + '</label></a></li>').appendTo('[data-filter=pack_code]');
        });
    };

    /**
     * @memberOf ui
     */
    ui.init_selectors = function init_selectors() {
        var spheres = $('[data-filter="sphere_code"]');
        var types = $('[data-filter="type_code"]');

        var heroesSpheres = app.deck.get_heroes_spheres();
        var defaultType = 'hero';

        if (heroesSpheres.length) {
            heroesSpheres.push('neutral');
            defaultType = 'ally';
        } else {
            heroesSpheres = ['spirit', 'tactics', 'leadership', 'lore', 'neutral'];
        }

        _.each(heroesSpheres, function(sphere) {
            spheres.find('input[name=' + sphere + ']').prop("checked", true).parent().addClass('active');
        });

        types.find('input[name=' + defaultType + ']').prop("checked", true).parent().addClass('active');
    };

    function uncheck_all_others() {
        $(this).closest('[data-filter]').find("input[type=checkbox]").prop("checked", false);
        $(this).children('input[type=checkbox]').prop("checked", true).trigger('change');
    }

    function check_all_others() {
        $(this).closest('[data-filter]').find("input[type=checkbox]").prop("checked", true);
        $(this).children('input[type=checkbox]').prop("checked", false);
    }

    function uncheck_all_active() {
        $(this).closest('[data-filter]').find("label.active").button('toggle');
    }

    function check_all_inactive() {
        $(this).closest('[data-filter]').find("label:not(.active)").button('toggle');
    }

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_click_filter = function(event) {
        var dropdown = $(this).closest('ul').hasClass('dropdown-menu');

        if (dropdown) {
            if (event.shiftKey) {
                if (!event.altKey) {
                    uncheck_all_others.call(this);
                } else {
                    check_all_others.call(this);
                }
            }
            event.stopPropagation();
        } else {
            if (!event.shiftKey && Config['buttons-behavior'] === 'exclusive' || event.shiftKey && Config['buttons-behavior'] === 'cumulative') {
                if (!event.altKey) {
                    uncheck_all_active.call(this);
                } else {
                    check_all_inactive.call(this);
                }
            }
        }
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_input_smartfilter = function() {
        var q = $(this).val();

        if (q.match(/^\w[:<>!]/)) {
            app.smart_filter.update(q);
        } else {
            app.smart_filter.update('');
        }

        ui.refresh_list();
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_submit_form = function() {
        var deck_json = app.deck.get_json();

        $('input[name="content"]').val(deck_json);
        $('input[name="description"]').val($('textarea[name="description_"]').val());
        $('input[name="tags"]').val($('input[name="tags_"]').val());
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_config_change = function() {
        var name = $(this).attr('name');
        var type = $(this).prop('type');

        switch (type) {
            case 'radio':
                var value = $(this).val();
                if (!isNaN(parseInt(value, 10))) {
                    value = parseInt(value, 10);
                }
                Config[name] = value;
                break;

            case 'checkbox':
                Config[name] = $(this).prop('checked');
                break;
        }

        ui.write_config_to_storage();

        switch (name) {
            case 'buttons-behavior':
                break;

            case 'core-set':
                ui.set_max_qty();
                ui.reset_list();
                break;

            case 'display-column':
                ui.update_list_template();
                ui.refresh_list();
                break;

            case 'show-suggestions':
                ui.toggle_suggestions();
                break;

            case 'compact-mode':
                ui.toggle_compact_mode();
                break;

            default:
                ui.refresh_list();
        }
    };

    ui.toggle_suggestions = function() {
        if (Config['show-suggestions'] == 0) {
            $('#table-suggestions').hide();
        } else {
            $('#table-suggestions').show();

            if (app.suggestions) {
                app.suggestions.setup();
                app.suggestions.number = Config['show-suggestions'];
                app.suggestions.isLoaded && app.suggestions.compute();
            }
        }
    };

    ui.toggle_compact_mode = function() {
        if (Config['compact-mode']) {
            $('#collection').addClass('compact');
        } else {
            $('#collection').removeClass('compact');
        }
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_table_sort_click = function(event) {
        event.preventDefault();
        var new_sort = $(this).data('sort');

        if (SortKey == new_sort) {
            SortOrder *= -1;
        } else {
            SortKey = new_sort;
            SortOrder = 1;
        }

        ui.refresh_list();
        ui.update_sort_caret();
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_list_quantity_change = function() {
        var row = $(this).closest('.card-container');
        var code = row.data('code');
        var quantity = parseInt($(this).val(), 10);

        ui.on_quantity_change(code, quantity);
    };

    ui.on_modal_quantity_change = function() {
        var modal = $('#cardModal');
        var code = modal.data('code');
        var input = $(this);

        var quantity = parseInt(input.val(), 10);
        var type = input.attr('name');

        ui.on_quantity_change(code, quantity, type == 'side-qty');

        modal.modal('hide');

        setTimeout(function() {
            $('#filter-text').typeahead('val', '');
 
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
              return;
            }

            $('#filter-text').focus();
        }, 100);
    };

    ui.on_modal_move_cards = function() {
        var modal = $('#cardModal');
        var code = modal.data('code');
        var button = $(this);

        var direction = button.data('direction');

        var card = app.data.cards.findById(code);
        if (!card) {
            return false;
        }

        if (direction == 'left' && card.insideboard > 0) {
            ui.on_quantity_change(code, card.insideboard - 1, true);
            ui.on_quantity_change(code, card.indeck + 1, false);
        }

        if (direction == 'right' && card.indeck > 0) {
            ui.on_quantity_change(code, card.indeck - 1, false);
            ui.on_quantity_change(code, card.insideboard + 1, true);
        }

        app.card_modal.updateModal();
    };

    ui.on_modal_key_press = function(event) {
        var num = parseInt(event.which, 10) - 48;
        $('#cardModal').find('input[type=radio][name=qty][value=' + num + ']').trigger('change');
    };

    ui.refresh_row = function(card_code, quantity) {
        // for each set of divs (1, 2, 3 columns)
        CardDivs.forEach(function(rows) {
            var row = rows[card_code];
            if (!row) {
                return;
            }

            // rows[card_code] is the card row of our card
            // for each "quantity switch" on that row
            row.find('input[name="qty-' + card_code + '"]').each(function(i, element) {
                // if that switch is NOT the one with the new quantity, uncheck it
                // else, check it
                if ($(element).val() != quantity) {
                    $(element).prop('checked', false).closest('label').removeClass('active');
                } else {
                    $(element).prop('checked', true).closest('label').addClass('active');
                }
            });
        });
    };

    /**
     * @memberOf ui
     */
    ui.on_quantity_change = function(card_code, quantity, is_sideboard) {
        var card_quantity = app.deck.set_card_copies(card_code, quantity, is_sideboard);
        ui.refresh_deck();
        ui.refresh_row(card_code, card_quantity);
    };

    /**
     * sets up event handlers ; dataloaded not fired yet
     * @memberOf ui
     */
    ui.setup_event_handlers = function() {
        $('[data-filter]').on({
            change: ui.refresh_list,
            click: ui.on_click_filter
        }, 'label');

        $('#filter-text').on('input', ui.on_input_smartfilter);

        $('#save_form').on('submit', ui.on_submit_form);

        $('#btn-save-as-copy').on('click', function() {
            $('#deck-save-as-copy').val(1);
        });

        $('#btn-cancel-edits').on('click', function() {
            var unsaved_edits = app.deck_history.get_unsaved_edits();
            if (unsaved_edits.length) {
                var confirmation = confirm("This operation will revert the changes made to the deck since " + unsaved_edits[0].date_creation.calendar() + ". The last " + (unsaved_edits.length > 1 ? unsaved_edits.length + " edits" : "edit") + " will be lost. Do you confirm?");
                if (!confirmation) {
                    return false;
                }
            } else {
                if (app.deck_history.is_changed_since_last_autosave()) {
                    var confirmation = confirm("This operation will revert the changes made to the deck. Do you confirm?");
                    if (!confirmation) {
                        return false;
                    }
                }
            }
            $('#deck-cancel-edits').val(1);
        });

        $('#config-options').on('change', 'input', ui.on_config_change);
        $('#collection').on('change', 'input[type=radio]', ui.on_list_quantity_change);

        // Card Modal
        $('#cardModal')
            .on('keypress', ui.on_modal_key_press)
            .on('change', 'input[type=radio]', ui.on_modal_quantity_change)
            .on('click', '.modal-move-card-container label', ui.on_modal_move_cards);

        $('thead').on('click', 'a[data-sort]', ui.on_table_sort_click);

        $('#menu-sort').on('click', 'a[id]', ui.do_action_deck);
    };

    ui.do_action_deck = function(event) {
        var action_id = $(this).attr('id');
        if (!action_id) {
            return;
        }

        switch (action_id) {
            case 'btn-sort-type':
                ui.refresh_deck({
                    sort: 'type',
                    cols: 2
                });
                break;

            case 'btn-sort-position':
                ui.refresh_deck({
                    sort: 'position',
                    cols: 1
                });
                break;

            case 'btn-sort-sphere':
                ui.refresh_deck({
                    sort: 'sphere',
                    cols: 1
                });
                break;

            case 'btn-sort-name':
                ui.refresh_deck({
                    sort: 'name',
                    cols: 1
                });
                break;
        }
    };

    /**
     * returns the current card filters as an array
     * @memberOf ui
     */
    ui.get_filters = function() {
        var filters = {};

        $('[data-filter]').each(
            function(index, div) {
                var column_name = $(div).data('filter');
                var arr = [];

                $(div).find("input[type=checkbox]").each(function(index, elt) {
                    if ($(elt).prop('checked')) arr.push($(elt).attr('name'));
                });

                if (arr.length) {
                    filters[column_name] = {
                        '$in': arr
                    };
                }
            }
        );
        return filters;
    };

    /**
     * updates internal variables when display columns change
     * @memberOf ui
     */
    ui.update_list_template = function() {
        switch (Config['display-column']) {
            case 1:
                DisplayColumnsTpl = _.template([
                    '<tr>',
                    '<td><div class="btn-group" data-toggle="buttons"><%= radios %></div></td>',
                    '<td><a class="card card-tip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a> <small class="text-muted">(<%= card.pack_code %>)</small></td>',
                    '<td class="sphere"><span class="icon-<%= card.sphere_code %> fg-<%= card.sphere_code %>" title="<%= card.sphere_name %>"></span></td>',
                    '<td class="type"><span class="icon-<%= card.type_code %>" title="<%= card.type_name %>"></span></td>',
                    '<td class="cost"><%= card.cost %><%= card.threat %> <span class="visible-xs-inline"><% if (card.threat != undefined) { %>T<% } else {%>C<% } %></span></td>',
                    '<td class="willpower"><% if (card.willpower != undefined) { %><%= card.willpower %> <span class="icon-willpower visible-xs-inline"></span><% } %></td>',
                    '<td class="attack"><% if (card.attack != undefined) { %><%= card.attack %> <span class="icon-attack visible-xs-inline"></span><% } %></td>',
                    '<td class="defense"><% if (card.defense != undefined) { %><%= card.defense %> <span class="icon-defense visible-xs-inline"></span><% } %></td>',
                    '<td class="health"><% if (card.health != undefined) { %><%= card.health %> <span class="icon-health visible-xs-inline"></span><% } %></td>',
                    '</tr>'
                ].join(''));
                break;

            case 2:
                DisplayColumnsTpl = _.template([
                    '<div class="col-sm-6">',
                    '<div class="media">',
                    '<div class="media-left"><img class="media-object" src="/bundles/cards/<%= card.code %>.png" alt="<%= card.name %>"></div>',
                    '<div class="media-body">',
                    '<h4 class="media-heading"><a class="card card-tip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></h4>',
                    '<div class="btn-group" data-toggle="buttons"><%= radios %></div>',
                    '</div>',
                    '</div>',
                    '</div>'
                ].join(''));
                break;

            case 3:
                DisplayColumnsTpl = _.template([
                    '<div class="col-sm-4">',
                    '<div class="media">',
                    '<div class="media-left"><img class="media-object" src="/bundles/cards/<%= card.code %>.png" alt="<%= card.name %>"></div>',
                    '<div class="media-body">',
                    '<h5 class="media-heading"><a class="card card-tip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></h5>',
                    '<div class="btn-group" data-toggle="buttons"><%= radios %></div>',
                    '</div>',
                    '</div>',
                    '</div>'
                ].join(''));
                break;
        }
    };

    /**
     * builds a row for the list of available cards
     * @memberOf ui
     */
    ui.build_row = function(card) {
        var radios = '';

        for (var i = 0; i <= card.maxqty; i++) {
            radios += '<label class="btn btn-xs btn-default"><input type="radio" name="qty-' + card.code + '" value="' + i + '">' + i + '</label>';
        }

        var html = DisplayColumnsTpl({
            radios: radios,
            url: Routing.generate('cards_zoom', { card_code: card.code }),
            card: card
        });

        return $(html);
    };

    ui.reset_list = function() {
        CardDivs = [[], [], []];
        ui.refresh_list();
    };

    /**
     * destroys and rebuilds the list of available cards
     * don't fire unless 250ms has passed since last invocation
     * @memberOf ui
     */
    ui.refresh_list = _.debounce(function refresh_list() {
        var counter = 0;
        var container = $('#collection-table').empty();
        var grid = $('#collection-grid').empty();
        var filters = Config['show-only-deck'] ? {} : ui.get_filters();

        var query = app.smart_filter.get_query(filters);
        var orderBy = {};

        SortKey.split('|').forEach(function(key) {
            orderBy[key] = SortOrder;
        });

        if (SortKey !== 'name') {
            orderBy['name'] = 1;
        }

        var cards = app.data.cards.find(query, { '$orderBy': orderBy });
        var divs = CardDivs[Config['display-column'] - 1];

        cards.forEach(function(card) {
            if (Config['show-only-deck'] && !card.indeck && !card.insideboard) {
                return;
            }

            var unusable = !app.deck.can_include_card(card);
            if (!Config['show-unusable'] && unusable) {
                return;
            }

            var row = divs[card.code];
            if (!row) {
                row = divs[card.code] = ui.build_row(card);
            }

            if (card.is_unique) {
                row.find('a').css('font-weight', 'bold');
            }

            row.data("code", card.code).addClass('card-container');

            if (unusable) {
                row.find('label').addClass("disabled").find('input[type=radio]').attr("disabled", true);
            } else {
                var radio = row.find('input[name="qty-' + card.code + '"][value="' + card.indeck + '"]');
                radio.prop('checked', true).closest('label').addClass('active');
            }

            if (Config['display-column'] > 1 && (counter % Config['display-column'] === 0)) {
                container = $('<div class="row"></div>').appendTo($('#collection-grid'));
            }

            container.append(row);
            counter++;
        });
    }, 250);

    /**
     * called when the deck is modified and we don't know what has changed
     * @memberOf ui
     */
    ui.on_deck_modified = function() {
        ui.refresh_deck();
        ui.refresh_list();
    };

    /**
     * @memberOf ui
     */
    ui.refresh_deck = function(options) {
        if (options) {
            DisplayOptions = options;
        }

        app.deck.display('#deck-content', DisplayOptions, false);
        app.deck.display('#sideboard-content', DisplayOptions, true);
        setTimeout(function() {
            app.draw_simulator && app.draw_simulator.reset();
            app.play_simulator && app.play_simulator.reset();
            app.deck_charts && app.deck_charts.setup();
            app.suggestions && Config['show-suggestions'] != 0 && app.suggestions.compute();
        }, 1);
    };

    /**
     * @memberOf ui
     */
    ui.setup_typeahead = function() {
        function findMatches(q, cb) {
            if (q.match(/^\w:/)) {
                return;
            }

            var name = app.data.get_searchable_string(q);
            var regexp1 = new RegExp('^' + name, 'i');
            var regexp2 = new RegExp('.+' + name, 'i');
            var startsWith = app.data.cards.find({ s_name: regexp1 });
            var contains = app.data.cards.find({ s_name: regexp2 });
            cb(startsWith.concat(contains));
        }

        $('#filter-text').typeahead({
            hint: true,
            highlight: true,
            minLength: 2
        }, {
            name: 'cardnames',
            displayKey: 'name',
            source: findMatches,
            limit: 10,
            templates: {
                suggestion: function(card) {
                    return $('<div class="fg-' + card.sphere_code + '"><span class="icon-fw icon-' + card.sphere_code + '"></span> <strong>' + card.name + '</strong> <small><i>' + card.pack_name + '</i></small></div>');
                }
            }
        });
    };

    ui.update_sort_caret = function() {
        var elt = $('[data-sort="' + SortKey + '"]');
        $(elt).closest('tr').find('th').removeClass('dropup').find('span.caret').remove();
        $(elt).after('<span class="caret"></span>').closest('th').addClass(SortOrder > 0 ? '' : 'dropup');
    };

    ui.init_filter_help = function() {
        $('#filter-text-button').popover({
            container: 'body',
            content: app.smart_filter.get_help(),
            html: true,
            placement: 'bottom',
            title: 'Smart filter syntax'
        });
    };

    ui.setup_dataupdate = function() {
        $('a.data-update').click(function(event) {
            $(document).on('data.app', function(event) {
                $('a.data-update').parent().text("Data refreshed. You can save or reload your deck.");
            });
            app.data.update();
            return false;
        })
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        ui.set_cores_qty();
        ui.init_config_buttons();
        ui.init_filter_help();
        ui.update_sort_caret();
        ui.toggle_suggestions();
        ui.toggle_compact_mode();
        ui.setup_event_handlers();
        app.textcomplete && app.textcomplete.setup('#description');
        app.markdown && app.markdown.setup('#description', '#description-preview');
        app.draw_simulator && app.draw_simulator.on_dom_loaded();
        app.play_simulator && app.play_simulator.on_dom_loaded();
        app.card_modal && $('#filter-text').on('typeahead:selected typeahead:autocompleted', app.card_modal.typeahead);
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function() {
        ui.set_max_qty();
        app.draw_simulator && app.draw_simulator.on_data_loaded();
        app.play_simulator && app.play_simulator.on_data_loaded();
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function() {
        ui.update_list_template();
        ui.build_sphere_selector();
        ui.build_type_selector();
        ui.build_pack_selector();
        ui.init_selectors();
        ui.refresh_deck();
        ui.refresh_list();
        ui.setup_typeahead();
        ui.setup_dataupdate();
        app.deck_history && app.deck_history.setup('#history');
    };

    ui.read_config_from_storage();

})(app.ui, jQuery);
