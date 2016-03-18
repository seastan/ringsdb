(function ui_deck(ui, $) {

    var DisplayColumnsTpl = '';
    var SortKey = 'type_code';
    var SortOrder = 1;
    var CardDivs = [[], [], []];
    var Config = null;

    /**
     * reads ui configuration from localStorage
     * @memberOf ui
     */
    ui.read_config_from_storage = function read_config_from_storage() {
        if (localStorage) {
            var stored = localStorage.getItem('ui.deck.config');
            if (stored) {
                Config = JSON.parse(stored);
            }
        }
        Config = _.extend({
            'show-unusable': false,
            'show-only-deck': false,
            'display-column': 1,
            'core-set': 1,
            'show-suggestions': 0,
            'buttons-behavior': 'cumulative'
        }, Config || {});
    };

    /**
     * write ui configuration to localStorage
     * @memberOf ui
     */
    ui.write_config_to_storage = function write_config_to_storage() {
        if (localStorage) {
            localStorage.setItem('ui.deck.config', JSON.stringify(Config));
        }
    };

    /**
     * inits the state of config buttons
     * @memberOf ui
     */
    ui.init_config_buttons = function init_config_buttons() {
        // radio
        ['display-column', 'core-set', 'show-suggestions', 'buttons-behavior'].forEach(function (radio) {
            $('input[name=' + radio + '][value=' + Config[radio] + ']').prop('checked', true);
        });
        // checkbox
        ['show-unusable', 'show-only-deck'].forEach(function (checkbox) {
            if (Config[checkbox]) $('input[name=' + checkbox + ']').prop('checked', true);
        })
    };

    /**
     * sets the maxqty of each card
     * @memberOf ui
     */
    ui.set_max_qty = function set_max_qty() {
        app.data.cards.find().forEach(function (record) {
            var max_qty = Math.min(3, record.deck_limit);

            if (record.pack_code == 'core') {
                max_qty = Math.min(max_qty, record.quantity * Config['core-set']);
            }

            app.data.cards.updateById(record.code, {
                maxqty: max_qty
            });
        });
    };

    /**
     * builds the sphere selector
     * @memberOf ui
     */
    ui.build_sphere_selector = function build_sphere_selector() {
        var filter = $('[data-filter=sphere_code]').empty();

        var sphere_codes = app.data.cards.distinct('sphere_code').sort();

        sphere_codes.splice(sphere_codes.indexOf('neutral'), 1);
        sphere_codes.unshift('neutral');
        sphere_codes.splice(sphere_codes.indexOf('baggins'), 1);
        sphere_codes.push('baggins');

        sphere_codes.forEach(function(sphere_code) {
            var example = app.data.cards.find({ 'sphere_code': sphere_code })[0];
            var label = $('<label class="btn btn-default btn-sm" data-code="' + sphere_code + '" title="' + example.sphere_name + '"></label>');
            label.append('<input type="checkbox" name="' + sphere_code + '"><span class="icon-' + sphere_code + '"></span>').tooltip({ container: 'body' });

            filter.append(label);
        });
        filter.button();
    };

    /**
     * builds the type selector
     * @memberOf ui
     */
    ui.build_type_selector = function build_type_selector() {
        var filter = $('[data-filter=type_code]').empty();

        var type_codes = app.data.cards.distinct('type_code').sort();
        var neutral_index = type_codes.indexOf('hero');
        type_codes.splice(neutral_index, 1);
        type_codes.unshift('hero');

        type_codes.forEach(function (type_code) {
            var example = app.data.cards.find({ 'type_code': type_code})[0];
            var label = $('<label class="btn btn-default btn-sm" data-code="' + type_code + '" title="' + example.type_name + '"></label>');
            label.append('<input type="checkbox" name="' + type_code + '"><span class="icon-' + type_code + '"></span>').tooltip({ container: 'body' });

            filter.append(label);
        });
        filter.button();
    };

    /**
     * builds the pack selector
     * @memberOf ui
     */
    ui.build_pack_selector = function build_pack_selector() {
        $('[data-filter=pack_code]').empty();

        app.data.packs.find({
            name: {
                '$exists': true
            }
        }).forEach(function (record) {
            // checked or unchecked ? checked by default
            var checked = true;
            // if not yet available, uncheck pack
            if (record.available === "") {
                checked = false;
            }
            // if user checked it previously, check pack
            if (localStorage && localStorage.getItem('set_code_' + record.code) !== null) {
                checked = true;
            }
            // if pack used by cards in deck, check pack
            var cards = app.data.cards.find({
                pack_code: record.code,
                indeck: {
                    '$gt': 0
                }
            });
            if (cards.length) {
                checked = true;
            }

            $('<li><a href="#"><label><input type="checkbox" name="' + record.code + '"' + (checked ? ' checked="checked"' : '') + '>' + record.name + '</label></a></li>').appendTo('[data-filter=pack_code]');
        });
    };

    /**
     * @memberOf ui
     */
    ui.init_selectors = function init_selectors() {
        var spheres = $('[data-filter=sphere_code]');
        spheres.find('input[name=neutral]').prop("checked", true).parent().addClass('active');

        _.each(app.deck.get_heroes_spheres_code(), function(sphere) {
            spheres.find('input[name=' + sphere + ']').prop("checked", true).parent().addClass('active');
        });

        var types = $('[data-filter=type_code]');
        types.find('input[name=ally]').prop("checked", true).parent().addClass('active');
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
    ui.on_click_filter = function on_click_filter(event) {
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
    ui.on_input_smartfilter = function on_input_smartfilter(event) {
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
    ui.on_submit_form = function on_submit_form(event) {
        var deck_json = app.deck.get_json();
        $('input[name=content]').val(deck_json);
        $('input[name=description]').val($('textarea[name=description_]').val());
        $('input[name=tags]').val($('input[name=tags_]').val());
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_config_change = function on_config_change(event) {
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
                ui.refresh_list();
                break;
            default:
                ui.refresh_list();
        }
    };

    ui.toggle_suggestions = function toggle_suggestions() {
        if (Config['show-suggestions'] == 0) {
            $('#table-suggestions').hide();
        }
        else {
            $('#table-suggestions').show();
        }
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_table_sort_click = function on_table_sort_click(event) {
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
    ui.on_list_quantity_change = function on_list_quantity_change(event) {
        var row = $(this).closest('.card-container');
        var code = row.data('code');
        var quantity = parseInt($(this).val(), 10);
        //	row[quantity ? "addClass" : "removeClass"]('in-deck');
        ui.on_quantity_change(code, quantity);
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_modal_quantity_change = function on_modal_quantity_change(event) {
        var modal = $('#cardModal');
        var code = modal.data('code');
        var quantity = parseInt($(this).val(), 10);
        modal.modal('hide');
        ui.on_quantity_change(code, quantity);

        setTimeout(function () {
            $('#filter-text').typeahead('val', '').focus();
        }, 100);
    }

    ui.refresh_row = function refresh_row(card_code, quantity) {
        // for each set of divs (1, 2, 3 columns)
        CardDivs.forEach(function (rows) {
            var row = rows[card_code];
            if (!row) {
                return;
            }

            // rows[card_code] is the card row of our card
            // for each "quantity switch" on that row
            row.find('input[name="qty-' + card_code + '"]').each(function (i, element) {
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
    ui.on_quantity_change = function on_quantity_change(card_code, quantity) {
        var update_all = app.deck.set_card_copies(card_code, quantity);
        ui.refresh_deck();

        if (update_all) {
            ui.refresh_list();
        } else {
            ui.refresh_row(card_code, quantity);
        }
    };

    /**
     * sets up event handlers ; dataloaded not fired yet
     * @memberOf ui
     */
    ui.setup_event_handlers = function setup_event_handlers() {
        $('[data-filter]').on({
            change: ui.refresh_list,
            click: ui.on_click_filter
        }, 'label');

        $('#filter-text').on('input', ui.on_input_smartfilter);

        $('#save_form').on('submit', ui.on_submit_form);

        $('#btn-save-as-copy').on('click', function (event) {
            $('#deck-save-as-copy').val(1);
        });

        $('#btn-cancel-edits').on('click', function (event) {
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

        $('#cardModal').on('keypress', function (event) {
            var num = parseInt(event.which, 10) - 48;
            $('#cardModal input[type=radio][value=' + num + ']').trigger('change');
        });
        $('#cardModal').on('change', 'input[type=radio]', ui.on_modal_quantity_change);

        $('thead').on('click', 'a[data-sort]', ui.on_table_sort_click);
    };

    /**
     * returns the current card filters as an array
     * @memberOf ui
     */
    ui.get_filters = function get_filters() {
        var filters = {};

        $('[data-filter]').each(
            function (index, div) {
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
    ui.update_list_template = function update_list_template() {
        switch (Config['display-column']) {
            case 1:
                DisplayColumnsTpl = _.template([
                    '<tr>',
                    '<td><div class="btn-group" data-toggle="buttons"><%= radios %></div></td>',
                    '<td><a class="card card-tip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></td>',
                    '<td class="sphere"><span class="icon-<%= card.sphere_code %> fg-<%= card.sphere_code %>" title="<%= card.sphere_name %>"></span></td>',
                    '<td class="type"><span class="icon-<%= card.type_code %>" title="<%= card.type_name %>"></span></td>',
                    '<td class="cost"><%= card.cost %><%= card.threat %></td>',
                    '<td class="willpower"><%= card.willpower %></td>',
                    '<td class="attack"><%= card.attack %></td>',
                    '<td class="defense"><%= card.defense %></td>',
                    '<td class="health"><%= card.health %></td>',
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
        }
    };

    /**
     * builds a row for the list of available cards
     * @memberOf ui
     */
    ui.build_row = function build_row(card) {
        var radios = '', radioTpl = _.template(
            '<label class="btn btn-xs btn-default <%= active %>"><input type="radio" name="qty-<%= card.code %>" value="<%= i %>"><%= i %></label>'
        );

        for (var i = 0; i <= card.maxqty; i++) {
            radios += radioTpl({
                i: i,
                active: (i == card.indeck ? ' active' : ''),
                card: card
            });
        }

        var html = DisplayColumnsTpl({
            radios: radios,
            url: Routing.generate('cards_zoom', {card_code: card.code}),
            card: card
        });
        return $(html);
    };

    ui.reset_list = function reset_list() {
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
        var filters = ui.get_filters();
        var query = app.smart_filter.get_query(filters);
        var orderBy = {};

        SortKey.split('|').forEach(function (key) {
            orderBy[key] = SortOrder;
        });

        if (SortKey !== 'name') {
            orderBy['name'] = 1;
        }

        var cards = app.data.cards.find(query, { '$orderBy': orderBy });
        var divs = CardDivs[Config['display-column'] - 1];

        cards.forEach(function (card) {
            if (Config['show-only-deck'] && !card.indeck) {
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

            row.data("code", card.code).addClass('card-container');

            row.find('input[name="qty-' + card.code + '"]').each(function(i, element) {
                if ($(element).val() == card.indeck) {
                    $(element).prop('checked', true).closest('label').addClass('active');
                } else {
                    $(element).prop('checked', false).closest('label').removeClass('active');
                }
            });

            if (unusable) {
                row.find('label').addClass("disabled").find('input[type=radio]').attr("disabled", true);
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
    ui.on_deck_modified = function on_deck_modified() {
        ui.refresh_deck();
        ui.refresh_list();
        app.suggestions && app.suggestions.compute();
    };


    /**
     * @memberOf ui
     */
    ui.refresh_deck = function refresh_deck() {
        app.deck.display('#deck-content');
        app.draw_simulator && app.draw_simulator.reset();
        app.deck_charts && app.deck_charts.setup();
    };

    /**
     * @memberOf ui
     */
    ui.setup_typeahead = function() {
        function findMatches(q, cb) {
            if (q.match(/^\w:/)) {
                return;
            }
            var regexp = new RegExp(q, 'i');
            cb(app.data.cards.find({ name: regexp }));
        }

        $('#filter-text').typeahead({
            hint: true,
            highlight: true,
            minLength: 2
        }, {
            name: 'cardnames',
            displayKey: 'name',
            source: findMatches,
            templates: {
                suggestion: function(card) {
                    return $('<div class="fg-' + card.sphere_code + '"><span class="icon-fw icon-' + card.sphere_code + '"></span> <strong>' + card.name + '</strong> <small><i>' + card.pack_name + '</i></small></div>');
                }
            }
        });
    };

    ui.update_sort_caret = function update_sort_caret() {
        var elt = $('[data-sort="' + SortKey + '"]');
        $(elt).closest('tr').find('th').removeClass('dropup').find('span.caret').remove();
        $(elt).after('<span class="caret"></span>').closest('th').addClass(SortOrder > 0 ? '' : 'dropup');
    };

    ui.init_filter_help = function init_filter_help() {
        $('#filter-text-button').popover({
            container: 'body',
            content: app.smart_filter.get_help(),
            html: true,
            placement: 'bottom',
            title: 'Smart filter syntax'
        });
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
        ui.init_config_buttons();
        ui.init_filter_help();
        ui.update_sort_caret();
        ui.toggle_suggestions();
        ui.setup_event_handlers();
        app.textcomplete && app.textcomplete.setup('#description');
        app.markdown && app.markdown.setup('#description', '#description-preview')
        app.draw_simulator && app.draw_simulator.on_dom_loaded();
        app.card_modal && $('#filter-text').on('typeahead:selected typeahead:autocompleted', app.card_modal.typeahead);
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function on_data_loaded() {
        ui.set_max_qty();
        app.draw_simulator && app.draw_simulator.on_data_loaded();
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
        ui.update_list_template();
        ui.build_sphere_selector();
        ui.build_type_selector();
        ui.build_pack_selector();
        ui.init_selectors();
        ui.refresh_deck();
        ui.refresh_list();
        ui.setup_typeahead();
        app.deck_history && app.deck_history.setup('#history');
    };

    ui.read_config_from_storage();

})(app.ui, jQuery);
