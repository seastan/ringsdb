(function ui_deck(ui, $) {

    ui.confirm_delete = function() {
        $('#delete-deck-name').text(app.deck.get_name());
        $('#delete-deck-id').val(app.deck.get_id());
        $('#deleteModal').modal('show');
    };

    ui.do_action_deck = function (event) {
        var action_id = $(this).attr('id');
        if (!action_id) {
            return;
        }

        switch (action_id) {
            case 'btn-delete':
                ui.confirm_delete();
                break;

            case 'btn-print':
                window.print();
                break;

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

            case 'btn-export-bbcode':
                app.deck.export_bbcode();
                break;

            case 'btn-export-markdown':
                app.deck.export_markdown();
                break;

            case 'btn-export-plaintext':
                app.deck.export_plaintext();
                break;

            case 'btn-export-html':
                app.deck.export_html();
                break;
        }
    };

    /**
     * sets up event handlers ; dataloaded not fired yet
     * @memberOf ui
     */
    ui.setup_event_handlers = function() {
        $('#btn-group-deck').on({
            click: ui.do_action_deck
        }, 'button[id], a[id]');
    };

    /**
     * @memberOf ui
     */
    ui.refresh_deck = function(options) {
        app.deck.display('#deck-content', options, false);
        app.deck.display('#sideboard-content', options, true);
        app.draw_simulator && app.draw_simulator.reset();
        app.play_simulator && app.play_simulator.reset();
        app.deck_charts && app.deck_charts.setup();
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
        ui.setup_event_handlers();
        app.draw_simulator && app.draw_simulator.on_dom_loaded();
        app.play_simulator && app.play_simulator.on_dom_loaded();
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function on_data_loaded() {
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
        var description = app.deck.get_description_md() || '*No description.*'; 
        var SCRIPT_REGEX = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
        while (SCRIPT_REGEX.test(description)) {
            description = description.replace(SCRIPT_REGEX, '');
        }

        app.markdown && app.markdown.update(description, '#description');
        ui.refresh_deck();
    };

})(app.ui, jQuery);
