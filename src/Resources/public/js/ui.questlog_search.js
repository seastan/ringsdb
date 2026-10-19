(function(ui, $) {
    ui.handle_checkbox_change = function handle_checkbox_change() {
        var $allowed = $('#allowed_packs');
        var $official = $allowed.find('input[name="packs[]"]');
        var $custom = $allowed.find('input[data-custom-pack]');
        $('#packs-on').text($official.filter(':checked').size() + $custom.filter(':checked').size());
        $('#packs-off').text($official.filter(':not(:checked)').size() + $custom.filter(':not(:checked)').size());
    };

    ui.add_custom_pack_section = function add_custom_pack_section() {
        var customPacks = app.user && app.user.customPacks;
        if (!customPacks || !customPacks.length) { return; }
        var $allowed = $('#allowed_packs');
        if (!$allowed.length || $allowed.find('#custom-pack-section').length) { return; }

        // Restore which custom packs were explicitly selected in the previous search.
        var selectedCodes = {};
        if (window.URLSearchParams) {
            new URLSearchParams(window.location.search).getAll('custom_packs[]').forEach(function(code) {
                selectedCodes[code] = true;
            });
        }

        var $section = $('<div id="custom-pack-section"></div>');
        $section.append('<p><small>My Custom Packs</small></p>');
        _.forEach(customPacks, function(cp) {
            var checked = selectedCodes[cp.code] ? ' checked="checked"' : '';
            $section.append(
                '<div class="checkbox"><label>'
                + '<input type="checkbox" data-custom-pack="' + cp.code + '"' + checked + '> '
                + cp.name
                + '</label></div>'
            );
        });
        $allowed.append($section);

        ui.handle_checkbox_change();

        $allowed.on('change', 'input[data-custom-pack]', ui.handle_checkbox_change);

        // On submit, inject hidden inputs so custom pack selection survives the page reload.
        $allowed.closest('form').on('submit', function() {
            var $form = $(this);
            $form.find('input[name="custom_packs[]"]').remove();
            $section.find('input[data-custom-pack]:checked').each(function() {
                $('<input type="hidden" name="custom_packs[]">').val($(this).data('custom-pack')).appendTo($form);
            });
        });

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

        $('#card').typeahead({
            hint: true,
            highlight: true,
            minLength: 2,
            limit:10
        }, {
            name: 'cardnames',
            displayKey: 'name',
            source: findMatches,
            limit: 10,
            templates: {
                suggestion: function(card) {
                    return $('<div class="fg-' + card.sphere_code + '"><span class="icon-fw icon-' + card.sphere_code + '"></span> <strong>' + app.data.display_name(card) + '</strong> <small><i>' + card.type_name + '</i></small></div>');
                }
            }
        });


        $('#card').on('typeahead:selected typeahead:autocompleted', function(event, data) {
            var card = app.data.cards.find({
                code: data.code
            })[0];

            var line = $('<p class="fg-' + card.sphere_code + '" style="padding: 3px 5px; border-radius: 3px; border: 1px solid silver"><button type="button" class="close" aria-hidden="true">&times;</button><input type="hidden" name="cards[]" value="' + card.code + '">' + app.data.display_name(card) + ' <small><i>' + card.type_name + '</i></small></p>');
            line.on({
                click: function(event) {
                    line.remove();
                }
            });
            line.insertBefore($('#card'));
            $(event.target).typeahead('val', '');
        });
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
        $('#allowed_packs').on('change', ui.handle_checkbox_change);

        $('#select_all').on('click', function(event) {
            $('#allowed_packs').find('input[type="checkbox"]:not(:checked)').prop('checked', true);
            ui.handle_checkbox_change();
            return false;
        });

        $('#select_none').on('click', function(event) {
            $('#allowed_packs').find('input[type="checkbox"]:checked').prop('checked', false);
            ui.handle_checkbox_change();
            return false;
        });
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function() {
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
        ui.setup_typeahead();
    };

    $(document).on('custom_packs_loaded', ui.add_custom_pack_section);

})(app.ui, jQuery);
