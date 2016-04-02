(function(ui, $) {

    ui.confirm_delete = function() {
        $('#delete-fellowship-name').text(Fellowship.name);
        $('#delete-fellowship-id').val(Fellowship.id);
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

            case 'btn-export-bbcode':
                app.deck.export_bbcode(true);
                break;

            case 'btn-export-markdown':
                app.deck.export_markdown(true);
                break;

            case 'btn-export-plaintext':
                app.deck.export_plaintext(true);
                break;

            case 'btn-download-text':
                event.preventDefault();
                ui.download_text(Fellowship.id);
                break;

            case 'btn-download-octgn':
                event.preventDefault();
                ui.download_octgn(Fellowship.id);
                break;

            case 'btn-log-quest':
                event.preventDefault();
                event.stopPropagation();

                var ids = _.pluck(Decks, 'id');
                var publisheds = _.pluck(Decks, 'is_published');
                ui.log_quest({
                    'deck1_id': ids[0],
                    'deck2_id': ids[1],
                    'deck3_id': ids[2],
                    'deck4_id': ids[3],
                    'deck1_is_decklist': publisheds[0],
                    'deck2_is_decklist': publisheds[1],
                    'deck3_is_decklist': publisheds[2],
                    'deck4_is_decklist': publisheds[3]
                });
                break;
        }
    };

    ui.download_text = function(id) {
        window.location = Routing.generate('fellowship_export_text', { fellowship_id: id });
    };

    ui.download_octgn = function(id) {
        window.location = Routing.generate('fellowship_export_octgn', { fellowship_id: id });
    };

    ui.log_quest = function(data) {
        window.location = Routing.generate('questlog_new', data);
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
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        ui.setup_event_handlers();

        var deckcount = 0;

        _.each(Decks, function(deck, key) {
            if (deck) {
                deckcount++;
            } else {
                $('#deck' + key + '-content').closest('.deck').remove();
            }
        });

        if (deckcount == 3) {
            $('.selected-decks .deck')
                .removeClass('col-md-3')
                .addClass('col-md-4');
        } else if (deckcount == 2) {
            $('.selected-decks .deck')
                .removeClass('col-md-3')
                .removeClass('col-sm-6')
                .addClass('col-md-3')
                .addClass('col-sm-6');

            $('<div class="col-md-6 col-sm-12"></div>').append($('#description')).appendTo('.selected-decks');
        } else if (deckcount == 1) {
            $('.selected-decks .deck')
                .removeClass('col-md-3')
                .removeClass('col-sm-6')
                .addClass('col-md-6')
                .find('.selected-deck-content')
                .removeClass('small');

            app.deck_selection.cols = 2;
            $('<div class="col-md-6"></div>').append($('#description')).appendTo('.selected-decks');
        }


        $('.selected-decks').removeClass('hidden');
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function() {
        app.deck_selection.headerOnly = false;
        app.deck_selection.refresh_deck();
    };
})(app.ui, jQuery);
