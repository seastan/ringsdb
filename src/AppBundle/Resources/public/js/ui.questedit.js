(function(ui, $) {

    ui.init_quest_selector = function() {
        var xhr;
        $('#quest').on('input', function() {
            if (xhr) {
                xhr.abort();
            }

            var quest_info = $('#quest-info').empty();

            var scenario = $(this).val();

            if (!scenario) {
                return;
            }

            xhr = $.ajax(Routing.generate('api_scenario', { scenario_id: scenario }), {
                type: 'GET',
                dataType: 'json',
                success: function(data, textStatus, jqXHR) {
                    $('<label />').text(data.name).appendTo(quest_info);

                    var ul = $('<ul class="encounter-sets" />').appendTo(quest_info);

                    _.each(data.encounters, function(encounter) {
                        var enc = encodeURIComponent(encounter.name);
                        $('<li />')
                            .append($('<span class="img" />').css('background-image', 'url("/bundles/app/images/encounters/' + enc + '.png")'))
                            .append(' ')
                            .append($('<a target="_blank" />').attr('href', 'http://hallofbeorn.com/Cards/Search?EncounterSet=' + enc).text(encounter.name))
                            .appendTo(ul);
                    });
                }
            });
        }).trigger('input');
    };

    ui.init_quest_mode_selector = function() {
        $('#difficulty').on('input', function() {
            var difficulty = $(this).val();

            $('#quest-info')
                .removeClass('easy')
                .removeClass('normal')
                .removeClass('nightmare')
                .addClass(difficulty);

        }).trigger('input');
    };

    ui.init_result_selector = function() {
        $('#victory').on('input', function() {
            var victory = $(this).val();

            if (victory == 'no') {
                $('#score').prop('disabled', true);
            } else {
                $('#score').prop('disabled', false);
            }
        }).trigger('input');
    };

    ui.init_date_picker = function() {
        $('#date')[0].valueAsDate = new Date();
    };

        /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        ui.init_quest_selector();
        ui.init_quest_mode_selector();
        ui.init_result_selector();
        ui.init_date_picker();

        app.deck_selection && app.deck_selection.init_buttons();
        app.markdown && app.markdown.init_markdown('#descriptionMd');
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
        app.textcomplete.setup('#descriptionMd');
        app.deck_selection && app.deck_selection.refresh_deck();
    };
})(app.ui, jQuery);
