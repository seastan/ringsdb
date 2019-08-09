(function(ui, $) {
    ui.handle_checkbox_change = function handle_checkbox_change() {
        $('#packs-on').text($('#allowed_packs').find('input[type="checkbox"]:checked').size());
        $('#packs-off').text($('#allowed_packs').find('input[type="checkbox"]:not(:checked)').size());
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


        $('#card').on('typeahead:selected typeahead:autocompleted', function(event, data) {
            var card = app.data.cards.find({
                code: data.code
            })[0];

            var line = $('<p class="fg-' + card.sphere_code + '" style="padding: 3px 5px; border-radius: 3px; border: 1px solid silver"><button type="button" class="close" aria-hidden="true">&times;</button><input type="hidden" name="cards[]" value="' + card.code + '">' + card.name + ' <small><i>' + card.pack_name + '</i></small></p>');
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

})(app.ui, jQuery);
