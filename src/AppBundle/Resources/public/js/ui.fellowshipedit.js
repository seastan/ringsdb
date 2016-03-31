(function(ui, $) {
    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
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
