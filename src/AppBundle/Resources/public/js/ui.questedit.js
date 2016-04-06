(function(ui, $) {

    var scenario_data = null;
    var difficulty = {};
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
                    scenario_data = data;

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

                    $('<div id="scenario-stats"></div>').appendTo(quest_info);

                    $('<small class="text-muted">Quest stats powered by <a href="http://hallofbeorn.com/Cards/Scenarios" target="_blank">Hall of Beorn</a>.</small>').appendTo(quest_info);

                    ui.update_scenatio_stats();
                }
            });
        }).trigger('input');

        $('#btn-randomize').on('click', function(e) {
            e.preventDefault();

            var select = $('#quest');
            var options = select.find('option');
            var random = ~~(Math.random() * options.length);

            options.eq(random).prop('selected', true);
            select.trigger('input');
        });
    };

    ui.update_scenatio_stats = function() {
        var scenario_stats = $('#scenario-stats').empty();

        if (!scenario_data) {
            return;
        }

        if (difficulty != 'normal' && !scenario_data['has_' + difficulty]) {
            console.log('quest has no ' + difficulty + ' mode');
            return;
        }

        $('<div></div>').html('<strong>' + scenario_data[difficulty + '_cards'] + ' cards</strong>').appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('enemies', 'enemy', 'enemies', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('locations', 'location', 'locations', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('treacheries', 'treachery', 'treacheries', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('objectives', 'objective', 'objectives', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('objective_allies', 'objective ally', 'objective allies', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('objective_locations', 'objective location', 'objective locations', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_card_count('encounter_side_quests', 'side quest', 'side quests', 'cards')).appendTo(scenario_stats);

        $('<div style="margin-top: 5px;"></div>').html(ui.get_effect_probability('surges', 'surge', 'cards')).appendTo(scenario_stats);
        $('<div></div>').html(ui.get_effect_probability('shadows', 'shadow', 'cards')).appendTo(scenario_stats);
    };

    ui.get_card_count = function(key, singular, plural, total) {
        var n = scenario_data[difficulty + '_' + key];

        if (!n) {
            return '';
        }

        var text = '&middot; ' + n + ' ' + (n == 1 ? singular : plural);
        if (total) {
            var t = scenario_data[difficulty + '_' + total];
            if (t) {
                text += ' (' + (100 * n / t).toFixed(0) + '%)';
            }
        }

        return text;
    };

    ui.get_effect_probability = function(key, label, total) {
        var n = scenario_data[difficulty + '_' + key];

        if (!n) {
            return '';
        }

        var text = '';
        if (total) {
            var t = scenario_data[difficulty + '_' + total];
            if (t) {
                text += (100 * n / t).toFixed(0) + '% chance of ';
                text += '<strong>' + label + '</strong> (';
                text += n + (n == 1 ? ' card)' : ' cards)');
            }
        }

        return text;
    };


    ui.init_quest_mode_selector = function() {
        $('#difficulty').on('input', function() {
            difficulty = $(this).val();

            $('#quest-info')
                .removeClass('easy')
                .removeClass('normal')
                .removeClass('nightmare')
                .addClass(difficulty);

            ui.update_scenatio_stats();
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
