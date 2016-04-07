(function(ui, $) {

    ui.setup_event_handlers = function() {
        $('.social .social-icon-like').on('click', ui.send_like);
        $('.social .social-icon-favorite').on('click', ui.send_favorite);

        $('#btn-group-deck').on({
            click: ui.do_action_deck
        }, 'button[id], a[id]');
    };

    ui.send_like = function(event) {
        event.preventDefault();
        var that = $(this);

        if (that.hasClass('processing')) {
            return;
        }

        that.addClass('processing');

        $.post(Routing.generate('fellowship_like'), {
            id: Fellowship.id
        }, function(data) {
            that.find('.num').text(data);
            that.removeClass('processing');
        });
    };

    ui.send_favorite = function(event) {
        event.preventDefault();

        var that = $(this);

        if (that.hasClass('processing')) {
            return;
        }

        that.addClass('processing');

        $.post(Routing.generate('fellowship_favorite'), {
            id: Fellowship.id
        }, function(data) {
            that.find('.num').text(data);

            var title = that.data('original-tooltip');
            that.data('original-tooltip', title == "Add to favorites" ? "Remove from favorites" : "Add to favorites");
            that.attr('title', that.data('original-tooltip'));
            that.removeClass('processing');
        });

        ui.send_like.call($('.social .social-icon-like'), event);
    };

    ui.setup_comment_form = function() {
        var form = $(
            '<form method="POST" action="' + Routing.generate('fellowship_comment') + '"><input type="hidden" name="id" value="' + Fellowship.id + '"><div class="form-group">' +
            '<textarea id="comment-form-text" class="form-control" rows="4" name="comment" placeholder="Enter your comment in Markdown format. Type # to enter a card name. Type $ to enter a symbol. Type @ to enter a user name."></textarea>' +
            '</div><div class="well text-muted" id="comment-form-preview"></div><button type="submit" class="btn btn-success">Submit comment</button></form>'
        ).insertAfter('#comment-form');

        var already_submitted = false;
        form.on('submit', function(event) {
            event.preventDefault();

            var data = $(this).serialize();

            if (already_submitted) {
                return;
            }

            already_submitted = true;

            $.ajax(Routing.generate('fellowship_comment'), {
                data: data,
                type: 'POST',
                success: function() {
                    form.replaceWith('<div class="alert alert-success" role="alert">Your comment has been posted. It will appear on the site in a few minutes.</div>');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
                    form.replaceWith('<div class="alert alert-danger" role="alert">An error occured while posting your comment (' + jqXHR.statusText + '). Reload the page and try again.</div>');
                }
            });
        });

        app.markdown.setup('#comment-form-text', '#comment-form-preview');
        app.textcomplete.setup('#comment-form-text', {
            cards: true,
            icons: true,
            users: Commenters
        });
    };

    ui.setup_social_icons = function() {
        if (!app.user.data || app.user.data.is_author || app.user.data.is_liked) {
            var element = $('.social .social-icon-like');
            element.replaceWith($('<span class="social-icon-like"></span>').html(element.html()));
        }

        if (!app.user.data) {
            var element = $('.social .social-icon-favorite');
            element.replaceWith($('<span class="social-icon-favorite"></span>').html(element.html()));
        } else if (app.user.data.is_favorite) {
            var element = $('.social .social-icon-favorite');
            element.attr('title', "Remove from favorites");
        } else {
            var element = $('.social .social-icon-favorite');
            element.attr('title', "Add to favorites");
        }

        if (!app.user.data) {
            var element = $('.social .social-icon-comment');
            element.replaceWith($('<span class="social-icon-comment"></span>').html(element.html()));
        }
    };

    ui.add_author_actions = function() {
        if (app.user.data && app.user.data.is_author) {
            $('#user_buttons').removeClass('hidden');

            if (!app.user.data.can_delete) {
                $('#btn-delete').remove();
            }
        } else {
            $('#user_buttons').remove();
        }
    };

    ui.setup_comment_hide = function() {
        if (app.user.data && app.user.data.is_author) {
            $('.comment-hide-button').remove();
            $('<a href="#" class="comment-hide-button"><span class="text-danger fa fa-times" style="margin-left:.5em"></span></a>').appendTo('.collapse.in .comment-date').on('click', function(event) {
                if (confirm('Do you really want to hide this comment for everybody?')) {
                    ui.hide_comment($(this).closest('td'));
                }
                return false;
            });
            $('<a href="#" class="comment-hide-button"><span class="text-success fa fa-check" style="margin-left:.5em"></span></a>').appendTo('.collapse:not(.in) .comment-date').on('click', function(event) {
                if (confirm('Do you really want to unhide this comment?')) {
                    ui.unhide_comment($(this).closest('td'));
                }
                return false;
            });
        }
    };

    ui.hide_comment = function(element) {
        var id = element.attr('id').replace(/comment-/, '');
        $.ajax(Routing.generate('fellowship_comment_hide', {comment_id: id, hidden: 1}), {
            type: 'POST',
            dataType: 'json',
            success: function(data, textStatus, jqXHR) {
                if (data === true) {
                    element.find('.collapse').collapse('hide');
                    element.find('.comment-toggler').show().prepend('The comment will be hidden for everyone in a few minutes.');
                    setTimeout(ui.setup_comment_hide, 1000);
                } else {
                    alert(data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
                alert('An error occured while hiding this comment (' + jqXHR.statusText + '). Reload the page and try again.');
            }
        });
    };

    ui.unhide_comment = function(element) {
        var id = element.attr('id').replace(/comment-/, '');
        $.ajax(Routing.generate('fellowship_comment_hide', {comment_id: id, hidden: 0}), {
            type: 'POST',
            dataType: 'json',
            success: function(data, textStatus, jqXHR) {
                if (data === true) {
                    element.find('.collapse').collapse('show');
                    element.find('.comment-toggler').hide();
                    setTimeout(ui.setup_comment_hide, 1000);
                } else {
                    alert(data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
                alert('An error occured while unhiding this comment (' + jqXHR.statusText + '). Reload the page and try again.');
            }
        });
    };

    ui.confirm_delete = function() {
        $('#delete-fellowship-name').text(Fellowship.name);
        $('#delete-fellowship-id').val(Fellowship.id);
        $('#deleteModal').modal('show');
    };

    ui.do_action_deck = function (event) {
        event.preventDefault();

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
                app.deck_selection.refresh_deck({
                    sort: 'type',
                    maxcols: 2
                });
                break;

            case 'btn-sort-position':
                app.deck_selection.refresh_deck({
                    sort: 'position',
                    maxcols: 1
                });
                break;

            case 'btn-sort-sphere':
                app.deck_selection.refresh_deck({
                    sort: 'sphere',
                    maxcols: 1
                });
                break;

            case 'btn-sort-name':
                app.deck_selection.refresh_deck({
                    sort: 'name',
                    maxcols: 1
                });
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


                var data = {};
                var ids = _.pluck(Decks, 'id');
                var publisheds = _.pluck(Decks, 'is_published');

                for (var i = 0; i < ids.length; i++) {
                    data['deck' + (i+1) + '_id'] = ids[i];
                    if (publisheds[i]) {
                        data['p' + (i+1)] = 1;
                    }
                }

                ui.log_quest(data);
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
        app.user.loaded.done(function() {
            ui.setup_comment_form();
            ui.add_author_actions();
            ui.setup_comment_hide();
        }).fail(function() {
            $('<p>You must be logged in to post comments.</p>').insertAfter('#comment-form');
        }).always(function() {
            app.deck_selection.headerOnly = false;
            app.deck_selection.refresh_deck();
            ui.setup_social_icons();
        });

    };
})(app.ui, jQuery);
