(function(ui, $) {

    ui.update_publish_button = function() {
        var count = 0;
        var published = 0;
        for (var i = 1; i <= 4; i++) {
            var id = $('input[name="deck' + i + '_id"]').val();
            var is_decklist = $('input[name="deck' + i + '_is_decklist"]').val();

            if (id) {
                count++;

                if (is_decklist == 'true') {
                    published++;
                }
            }
        }

        if (count && count == published) {
            $('#btn-save-and-publish').prop('disabled', false).removeClass('disabled');
        } else {
            $('#btn-save-and-publish').prop('disabled', true).addClass('disabled');
        }
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        $(document).on('deck-changed', ui.update_publish_button);

        app.deck_selection && app.deck_selection.init_buttons();
        app.markdown && app.markdown.init_markdown('#descriptionMd');
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function() {
        app.textcomplete.setup('#descriptionMd');
        app.deck_selection && app.deck_selection.refresh_deck();
    };
})(app.ui, jQuery);
