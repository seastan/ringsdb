(function(deck_selection, $) {

    var Decks = {};

    deck_selection.init = function(decks) {
        Decks = decks;
    };

    deck_selection.cols = 1;
    deck_selection.headerOnly = true;

    var modal_deck_number;
    deck_selection.init_buttons = function() {
        $('#deck-selection, #deckSelectionModal').on('click', 'a[data-action], label[data-action]', function(e) {
            var btn = $(this);
            var action = btn.data('action');
            if (!action) {
                return;
            }

            switch (action) {
                case 'show-cards':
                case 'hide-cards':
                    btn.addClass('hidden').siblings('a').removeClass('hidden');
                    deck_selection.headerOnly = !deck_selection.headerOnly;
                    deck_selection.refresh_deck();
                    break;

                case 'remove-deck':
                    var deck_number = btn.data('deck');
                    Decks[deck_number] = null;
                    deck_selection.activate_deck(deck_number);
                    deck_selection.display_deck();
                    break;

                case 'select-deck':
                    $('#deckSelectionModal').modal('show');
                    modal_deck_number = btn.data('deck');

                    $('#deckSelectionModal').find('label[data-action="my-decks"]').trigger('click');
                    break;

                case 'my-decks':
                    if (deck_list_xhr) {
                        deck_list_xhr.abort();
                    }

                    deck_selection.load_deck_list(modal_deck_number);
                    $('#deckSelectionAnotherPlayerBox').addClass('hidden');
                    btn.addClass('active').siblings().removeClass('active');
                    break;

                case 'other-decks':
                    if (deck_list_xhr) {
                        deck_list_xhr.abort();
                    }

                    $('#deckSelectionAnotherPlayerBox').removeClass('hidden');
                    btn.addClass('active').siblings().removeClass('active');
                    $('#deckSelectionList').empty();
                    break;
            }
        });
    };


    // Unfortunately, to allow many decks on a single screen at once would require big changes to app.deck and app.data.
    // We will activate each deck at once to ease this change.
    var selectedDeck = null;
    deck_selection.activate_deck = function(ix) {
        var deck = Decks[ix];
        selectedDeck = ix;
        if (deck) {
            app.deck.init(deck);
        }
    };

    deck_selection.display_deck = function() {
        if (Decks[selectedDeck]) {
            app.deck.display('#deck' + selectedDeck + '-content', { cols: deck_selection.cols, special_meta: true, header_only: deck_selection.headerOnly }, false);
            $('.selected-deck-placeholder').eq(selectedDeck - 1).addClass('hidden');
            $('.selected-deck-content').eq(selectedDeck - 1).removeClass('hidden');
            $('input[name="deck' + selectedDeck + '_id"]').val(Decks[selectedDeck].id);
        } else {
            $('#deck' + selectedDeck + '-content').empty();
            $('.selected-deck-placeholder').eq(selectedDeck - 1).removeClass('hidden');
            $('.selected-deck-content').eq(selectedDeck - 1).addClass('hidden');
            $('input[name="deck' + selectedDeck + '_id"]').val('');
        }
    };

    deck_selection.refresh_deck = function() {
        for (var i = 1; i <= 4; i++) {
            deck_selection.activate_deck(i);
            deck_selection.display_deck();
        }
    };

    deck_selection.display_deck_selection_list = function(deck_number, decks) {
        var tbody = $('#deckSelectionList').empty();
        var tr;
        var td;
        var tags;

        if (!decks || decks.success === false) {
            $('<tr />')
                .append($('<td />').text(decks.error || 'No decks found.'))
                .appendTo(tbody);

            return;
        }

        _.each(decks, function(deck) {
            var disabled = false;
            for (var i = 1; i <= 4; i++) {
                if (Decks[i] && Decks[i].id == deck.id) {
                    disabled = true;
                }
            }

            tr = $('<tr />').data('id', deck.id).appendTo(tbody);
            td = $('<td class="decklist-hero-image hidden-xs"/>').appendTo(tr);

            _.each(deck.heroes, function(count, hero) {
                var heroCard = app.data.cards.findById(hero);

                if (!heroCard) {
                    return;
                }
                $('<div class="decklist-hero"></div>')
                    .addClass('border-light-' + heroCard.sphere_code)
                    .append('<div class="hero-thumbnail card-thumbnail-4x card-thumbnail-hero" style="background-image:url(\'/bundles/cards/' + heroCard.code + '.png\')"></div>')
                    .appendTo(td);

                td.append(' ');
            });

            td = $('<td />').appendTo(tr);
            td.text(deck.name + ' ' + deck.version + ' ');

            if (deck.problem) {
                td.append('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> ' + app.deck.problem_labels[deck.problem] + '</div>');
            }

            tags = $('<div class="tags" />').appendTo(td);
            _.each(deck.tags.split(' '), function(tag) {
                $('<span class="tag" />').text(tag).appendTo(tags);
            });

            td = $('<td class="decks-actions text-right" />').appendTo(tr);
            var button = $('<a href="" class="btn btn-xs btn-default text-success" title="Select this Deck"><span class="fa fa-check fa-fw"></span></a>').appendTo(td);
            if (disabled) {
                button.addClass('disabled').css('cursor', 'not-allowed');
            } else {
                button.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    Decks[deck_number] = deck;
                    $('#deckSelectionModal').modal('hide');
                    deck_selection.activate_deck(deck_number);
                    deck_selection.display_deck();
                });
            }
        });
    };

    deck_selection.submit_search_user_decks = function(e) {
        e.stopPropagation();
        e.preventDefault();

        var username_or_url = $('#deckSelectionById').val();

        if (!username_or_url) {
            return;
        }

        var match = username_or_url.match(/\/view\/(\d+)/);
        if (match) {
            deck_selection.load_deck(modal_deck_number, match[1]);
        } else {
            deck_selection.load_user_deck_list(modal_deck_number, username_or_url);
        }
    };


    var deck_list_xhr = null;
    deck_selection.load_deck_list = function(deck_number) {
        $('#deckSelectionList').empty().append('<div class="deck-loading"><i class="fa fa-spinner fa-spin fa-5x"></i></div>');

        if (deck_list_xhr) {
            deck_list_xhr.abort();
        }

        deck_list_xhr = $.ajax(Routing.generate('api_private_my_decks'), {
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                deck_selection.display_deck_selection_list(deck_number, data);
            }
        });
    };

    deck_selection.load_user_deck_list = function(deck_number, username) {
        $('#deckSelectionList').empty().append('<div class="deck-loading"><i class="fa fa-spinner fa-spin fa-5x"></i></div>');

        if (deck_list_xhr) {
            deck_list_xhr.abort();
        }

        deck_list_xhr = $.ajax(Routing.generate('api_private_user_decks', { username: encodeURIComponent(username) }), {
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                deck_selection.display_deck_selection_list(deck_number, data);
            }
        });
    };

    deck_selection.load_deck = function(deck_number, deck_id) {
        $('#deckSelectionList').empty().append('<div class="deck-loading"><i class="fa fa-spinner fa-spin fa-5x"></i></div>');

        if (deck_list_xhr) {
            deck_list_xhr.abort();
        }

        deck_list_xhr = $.ajax(Routing.generate('api_private_load_deck', { id: deck_id }), {
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success === false) {
                    deck_selection.display_deck_selection_list(deck_number, data);
                } else {
                    deck_selection.display_deck_selection_list(deck_number, [data]);
                }
            }
        });
    };
})(app.deck_selection = {}, jQuery);
