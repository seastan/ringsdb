(function ui_decks(ui, $) {

    ui.decks = [];
    ui.cards_used = [];
    ui.cards_not_used = [];

    ui.confirm_delete = function confirm_delete(event) {
        var tr = $(this).closest('tr');
        var deck_id = tr.data('id');
        var deck_name = tr.find('.deck-name').text();
        $('#delete-deck-name').text(deck_name);
        $('#delete-deck-id').val(deck_id);
        $('#deleteModal').modal('show');
    };

    ui.confirm_delete_all = function confirm_delete_all(ids) {
        $('#delete-deck-list-id').val(ids.join('-'));
        $('#deleteListModal').modal('show');
    };

    ui.set_tags = function set_tags(id, tags) {
        var elt = $('tr[data-id=' + id + ']');
        var div = elt.find('div.tags').empty();
        tags.forEach(function(tag) {
            div.append($('<span class="tag">' + tag + '</span>'));
        });

        ui.update_tag_toggles();
    };

    ui.tag_add = function tag_add(ids) {
        $('#tag_add_ids').val(ids);
        $('#tagAddModal').modal('show');
        setTimeout(function() {
            $('#tag_add_tags').focus();
        }, 500);
    };

    ui.tag_add_process = function tag_add_process(event) {
        event.preventDefault();
        var ids = $('#tag_add_ids').val().split(/,/);
        var tags = $('#tag_add_tags').val().split(/\s+/);
        if (!ids.length || !tags.length) return;
        ui.tag_process_any('tag_add', {ids: ids, tags: tags});
    };

    ui.tag_remove = function tag_remove(ids) {
        $('#tag_remove_ids').val(ids);
        $('#tagRemoveModal').modal('show');
        setTimeout(function() {
            $('#tag_remove_tags').focus();
        }, 500);
    };

    ui.tag_remove_process = function tag_remove_process(event) {
        event.preventDefault();
        var ids = $('#tag_remove_ids').val().split(/,/);
        var tags = $('#tag_remove_tags').val().split(/\s+/);
        if (!ids.length || !tags.length) return;
        ui.tag_process_any('tag_remove', {ids: ids, tags: tags});
    };

    ui.tag_clear = function tag_clear(ids) {
        $('#tag_clear_ids').val(ids);
        $('#tagClearModal').modal('show');
    };

    ui.tag_clear_process = function tag_clear_process(event) {
        event.preventDefault();
        var ids = $('#tag_clear_ids').val().split(/,/);
        if (!ids.length) return;
        ui.tag_process_any('tag_clear', {ids: ids});
    };

    ui.tag_process_any = function tag_process_any(route, data) {
        $.ajax(Routing.generate(route), {
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(data, textStatus, jqXHR) {
                var response = jqXHR.responseJSON;
                if (!response.success) {
                    alert('An error occured while updating the tags.');
                    return;
                }
                $.each(response.tags, function(id, tags) {
                    ui.set_tags(id, tags);
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
                alert('An error occured while updating the tags.');
            }
        });
    };

    ui.update_tag_toggles = function() {
        var tags = [];

        $('#decks span[data-tag]').each(function(index, elt) {
            tags.push($(elt).data('tag'));
        });

        $('#tag_toggles').empty();

        _.uniq(tags).forEach(function(tag) {
            if (tag) {
                $('<button type="button" class="btn btn-default btn-xs" data-toggle="button" data-tag="' + tag + '">' + tag + '</button>').appendTo('#tag_toggles');
            }
        });
    };

    ui.filter_decks = function filter_decks() {
        // Get current tags
        var buttons = $('#tag_toggles button.active');
        var tags = [];
        buttons.each(function(index, button) {
            tags.push($(button).data('tag'));
        });
        // Start empty array of deckmatches. key = deckid, value = number of matches assigned to that deck id.
        var deckmatches = [];
        // We require the number of matches for a given deckid to be equal to the number of tags+cards_used. So it must match everything.
        var requiredmatches = tags.length + ui.cards_used.length;
        // Hide all rows
        $('#decks tr').hide();
        // Loop over tags
        if (tags.length) {
            tags.forEach(function(tag) {
                // Get all rows with matching tag
                $('#decks span[data-tag="' + tag + '"]').each(function(index, elt) {
                    // Get deckid
                    deckid = $(elt).closest('tr')[0].getAttribute('data-id');
                    // Either add or increment the number of matches for this deckid
                    if (deckid in deckmatches) {
                        deckmatches[deckid] = deckmatches[deckid] + 1;
                    } else {
                        deckmatches[deckid] = 1;
                    }
                });
            });
        }
        // Loop over cards_used
        if (ui.cards_used.length) {
            ui.cards_used.forEach(function(card) {
                // Get all rows with matching card code
                 $('#decks span[data-code="' + card.code + '"]').each(function(index, elt) {
                    // Get deckid
                    deckid = $(elt).closest('tr')[0].getAttribute('data-id');
                    // Either add or increment the number of matches for this deckid
                    if (deckid in deckmatches) {
                        deckmatches[deckid] = deckmatches[deckid] + 1;
                    } else {
                        deckmatches[deckid] = 1;
                    }
                }); 
            });
        }
        // Loop over all rows
        $('#decks tr').each(function(index, elt) {
            // Get deckid for this row
            deckid = elt.getAttribute('data-id');
            // Check to see if this deckid got the required number of matches. If it did, make its row visible.
            if (deckid in deckmatches){
                if (deckmatches[deckid] == requiredmatches) {
                    $(elt).show();
                }
            } else if (requiredmatches == 0) {
                $(elt).show();
            }
        });
        // Loop over cards_not_used
        if (ui.cards_not_used.length) {
            ui.cards_not_used.forEach(function(card) {
                // Get all rows with matching card code
                 $('#decks span[data-code="' + card.code + '"]').each(function(index, elt) {
                    // Hide the row
                    $(elt).closest('tr').hide();
                }); 
            });
        }
    };

    ui.do_diff = function(ids) {
        if (ids.length < 2) {
            return false;
        }

        location.href = Routing.generate('decks_diff', { deck1_id: ids[0], deck2_id: ids[1] });
    };

    ui.create_fellowship = function(ids) {
        if (ids.length < 1 || ids.length > 4) {
            return false;
        }

        location.href = Routing.generate('fellowship_new', { deck1_id: ids[0], deck2_id: ids[1], deck3_id: ids[2], deck4_id: ids[3] });
    };

    ui.create_quest = function(ids) {
        if (ids.length < 1 || ids.length > 4) {
            return false;
        }

        location.href = Routing.generate('questlog_new', { deck1_id: ids[0], deck2_id: ids[1], deck3_id: ids[2], deck4_id: ids[3] });
    };

    ui.download_text_selection = function(ids) {
        window.location = Routing.generate('deck_export_text_list', { ids: ids });
    };

    ui.download_octgn_selection = function(ids) {
        window.location = Routing.generate('deck_export_octgn_list', { ids: ids });
    };


    ui.do_action_selection = function do_action_selection(event) {
        event.stopPropagation();
        var action_id = $(this).attr('id');
        var ids = $('.list-decks input:checked').map(function(index, elt) {
            return $(elt).closest('tr').data('id');
        }).get();

        if (!action_id || !ids.length) {
            return;
        }
        switch (action_id) {
            case 'btn-fellowship':
                ui.create_fellowship(ids);
                break;
            case 'btn-quest':
                ui.create_quest(ids);
                break;
            case 'btn-compare':
                ui.do_diff(ids);
                break;
            case 'btn-tag-add':
                ui.tag_add(ids);
                break;
            case 'btn-tag-remove-one':
                ui.tag_remove(ids);
                break;
            case 'btn-tag-remove-all':
                ui.tag_clear(ids);
                break;
            case 'btn-delete-selected':
                ui.confirm_delete_all(ids);
                break;
            case 'btn-download-text':
                ui.download_text_selection(ids);
                break;
            case 'btn-download-octgn':
                ui.download_octgn_selection(ids);
                break;
        }
        return false;
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {

        $('#decks').on('click', 'button.btn-delete-deck', ui.confirm_delete);
        $('#decks').on('click', 'input[type=checkbox]', function(event) {
            var checked = $(this).closest('tbody').find('input[type=checkbox]:checked');
            var button = $('#btn-group-selection button');
            if (checked.size()) {
                button.removeClass('btn-default').addClass('btn-primary')
            } else {
                button.addClass('btn-default').removeClass('btn-primary')
            }

        });

        $('#btn-group-selection').on('click', 'button[id],a[id]', ui.do_action_selection);

        $('#tag_toggles').on('click', 'button', function(event) {
            var button = $(this);
            if (!event.shiftKey) {
                $('#tag_toggles button').each(function(index, elt) {
                    if ($(elt).text() != button.text()) $(elt).removeClass('active');
                });
            }
            setTimeout(ui.filter_decks, 0);
        });
        ui.update_tag_toggles();

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
        ui.setup_typeahead();
    };


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

        $('#card_used').typeahead({
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


        $('#card_used').on('typeahead:selected typeahead:autocompleted', function(event, data) {
            var card = app.data.cards.find({
                code: data.code
            })[0];
            ui.cards_used.push(card);

            var line = $('<p class="fg-' + card.sphere_code + '" style="padding: 3px 5px; border-radius: 3px; border: 1px solid silver"><button type="button" class="close" aria-hidden="true">&times;</button><input type="hidden" name="cards_used[]" value="' + card.code + '">' + card.name + ' <small><i>' + card.pack_name + '</i></small></p>');
            line.on({
                click: function(event) {
                    // Remove the line in the html 
                    line.remove();
                    // Find and remove the cards from the global variable
                    var index = ui.cards_used.indexOf(card);
                    if (index !== -1) ui.cards_used.splice(index, 1);
                    // Reset filter
                    setTimeout(ui.filter_decks, 0);
                }
            });
            line.insertBefore($('#card_used'));
            $(event.target).typeahead('val', '');
            setTimeout(ui.filter_decks, 0);
        });

        $('#card_not_used').typeahead({
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


        $('#card_not_used').on('typeahead:selected typeahead:autocompleted', function(event, data) {
            var card = app.data.cards.find({
                code: data.code
            })[0];
            ui.cards_not_used.push(card);

            var line = $('<p class="fg-' + card.sphere_code + '" style="padding: 3px 5px; border-radius: 3px; border: 1px solid silver"><button type="button" class="close" aria-hidden="true">&times;</button><input type="hidden" name="cards_not_used[]" value="' + card.code + '">' + card.name + ' <small><i>' + card.pack_name + '</i></small></p>');
            line.on({
                click: function(event) {
                    // Remove the line in the html 
                    line.remove();
                    // Find and remove the cards from the global variable
                    var index = ui.cards_not_used.indexOf(card);
                    if (index !== -1) ui.cards_not_used.splice(index, 1);
                    // Reset filter
                    setTimeout(ui.filter_decks, 0);
                }
            });
            line.insertBefore($('#card_not_used'));
            $(event.target).typeahead('val', '');
            setTimeout(ui.filter_decks, 0);
        });
    };


})(app.ui, jQuery);
