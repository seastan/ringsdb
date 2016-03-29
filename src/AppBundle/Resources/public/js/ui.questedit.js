(function ui_questlog_new(ui, $) {

    // Unfortunately, to allow many decks on a single screen at once would require big changes to app.deck and app.data.
    // We will activate each deck at once to ease this change.
    var selectedDeck = null;
    var headerOnly = true;
    ui.activate_deck = function(ix) {
        var deck = Decks[ix];
        if (deck) {
            app.deck.init(deck);
            selectedDeck = ix;
        } else {
            selectedDeck = null;
        }
    };

    ui.display_deck = function() {
        if (selectedDeck) {
            app.deck.display('#deck' + selectedDeck + '-content', { cols: 1, special_meta: true, header_only: headerOnly }, false);
            $('.selected-deck-placeholder').eq(selectedDeck - 1).addClass('hidden');
            $('.selected-deck-content').eq(selectedDeck - 1).removeClass('hidden');
        }
    };

    ui.refresh_deck = function() {
        for (var i = 1; i <= 4; i++) {
            ui.activate_deck(i);
            ui.display_deck();
        }
    };

    ui.init_buttons = function() {
        $('#btn-show-cards').on('click', function(e) {
            $(this).addClass('hidden');
            $('#btn-hide-cards').removeClass('hidden');
            headerOnly = false;
            ui.refresh_deck();
        });

        $('#btn-hide-cards').on('click', function(e) {
            $(this).addClass('hidden');
            $('#btn-show-cards').removeClass('hidden');
            headerOnly = true;
            ui.refresh_deck();
        });
    };

    ui.init_markdown = function() {
        $('#descriptionMd').markdown({
            iconlibrary: 'fa',
            hiddenButtons: ['cmdHeading', 'cmdImage', 'cmdCode'],
            footer: 'Press # to insert a card name, $ to insert a game symbol.',
            additionalButtons: [[{
                name: "groupCard",
                data: [{
                    name: "cmdCard",
                    title: "Turn a card name into a card link",
                    icon: "fa fa-clone",
                    callback: ui.on_button_card
                }]
            }, {
                name: "groupSymbol",
                data: [{
                    name: "cmdSymbol",
                    title: "Insert a game symbol",
                    icon: "icon-attack",
                    callback: ui.on_button_symbol
                }]
            }, {
                name: "groupCustom",
                data: [{
                    name: "cmdCustom1",
                    title: "Heading 1",
                    icon: "fa fa-header",
                    callback: _.partial(ui.on_button_heading, '#')
                }, {
                    name: "cmdCustom2",
                    title: "Heading 2",
                    icon: "fa fa-header small",
                    callback: _.partial(ui.on_button_heading, '##')
                }, {
                    name: "cmdCustom3",
                    title: "Heading 3",
                    icon: "fa fa-header smaller",
                    callback: _.partial(ui.on_button_heading, '###')
                }]
            }]]
        });
    };

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
        ui.init_buttons();
        ui.init_markdown();
        ui.init_quest_selector();
        ui.init_quest_mode_selector();
        ui.init_result_selector();
        ui.init_date_picker();
    };

    ui.on_button_heading = function(heading, e) {
        // Append/remove # surround the selection
        var chunk, cursor, selected = e.getSelection(), content = e.getContent(), pointer, prevChar;

        if (selected.length === 0) {
            // Give extra word
            chunk = e.__localize('heading text');
        } else {
            chunk = selected.text + '\n';
        }

        // transform selection and set the cursor into chunked text
        if ((pointer = heading.length + 2, content.substr(selected.start - pointer, pointer) === heading + ' ')
            || (pointer = heading.length + 1, content.substr(selected.start - pointer, pointer) === heading)) {
            e.setSelection(selected.start - pointer, selected.end);
            e.replaceSelection(chunk);
            cursor = selected.start - pointer;
        } else if (selected.start > 0 && (prevChar = content.substr(selected.start - 1, 1), !!prevChar && prevChar != '\n')) {
            e.replaceSelection('\n\n' + heading + ' ' + chunk);
            cursor = selected.start + heading.length + 4;
        } else {
            // Empty string before element
            e.replaceSelection(heading + ' ' + chunk);
            cursor = selected.start + heading.length + 1;
        }

        // Set the cursor
        e.setSelection(cursor, cursor + chunk.length);
    };

    ui.on_button_symbol = function ui_on_button_symbol(e) {
        var button = $('button[data-handler=bootstrap-markdown-cmdSymbol]');
        $(button).attr('data-toggle', 'dropdown');
        $(button).next().remove();

        var menu = $('<ul class="dropdown-menu">').insertAfter(button).on('click', 'li', function(event) {
            var icon = $(this).data('icon');
            var chunk = '<span class="icon-' + icon + '"></span>';
            ui.replace_selection(e, e.getSelection(), chunk);
            $(menu).remove();
            $(button).off('click');
        });

        var icons = 'spirit tactics lore leadership neutral baggins fellowship unique threat willpower attack defense health hero ally attachment event player-side-quest treasure'.split(' ');
        icons.forEach(function(icon) {
            menu.append('<li data-icon="' + icon + '"><a href="#"><span style="display: inline-block; width: 2em; text-align: center" class="icon-' + icon + '"></span> ' + icon + '</a></li>');
        });
        $(button).dropdown();
    };

    ui.on_button_card = function ui_on_button_card(e) {
        var button = $('button[data-handler=bootstrap-markdown-cmdCard]');

        button.attr('data-toggle', 'dropdown').next().remove();

        var menu = $('<ul class="dropdown-menu">').insertAfter(button).on('click', 'li', function(event) {
            var code = $(this).data('code');
            var name = $(this).data('name');

            var chunk = '[' + name + '](' + Routing.generate('cards_zoom', { card_code: code }) + ')';
            ui.replace_selection(e, e.getSelection(), chunk);
            $(menu).remove();
            button.off('click');
        });

        var cards = app.data.cards.find({ name: new RegExp(e.getSelection().text, 'i')}, { '$orderBy': { name: 1 }});
        if (cards.length > 10) {
            cards = cards.slice(0, 10);
        }
        cards.forEach(function(card) {
            menu.append('<li data-code="' + card.code + '" data-name="' + card.name + '"><a href="#">' + card.name + ' <small><i>' + card.pack_name + '</i></small></a></li>');
        });

        button.dropdown();
    };

    ui.replace_selection = function ui_replace_selection(e, selected, chunk) {
        e.replaceSelection(chunk);
        var cursor = selected.start;
        e.setSelection(cursor + chunk.length, cursor + chunk.length);
        e.$textarea.focus();
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
        app.textcomplete.setup('#descriptionMd');
        ui.refresh_deck();
    };
})(app.ui, jQuery);
