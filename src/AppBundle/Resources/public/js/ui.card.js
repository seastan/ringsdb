(function ui_card(ui, $) {

    /**
     * The user is loaded and they have written a review on the page
     */
    ui.setup_edit = function(review_id) {
        var button = $('<button class="btn btn-default" id="review-button"><span class="glyphicon glyphicon-pencil"></span> Edit review</a>');
        $('#review-' + review_id + ' .review-text').append(button);
        $('input[name="review_id"]').val(review_id);
    };

    /**
     * The user is loaded and they haven't written a review on the page yet
     */
    ui.setup_write = function() {
        var button = $('<button class="pull-right btn btn-default" id="review-button"><span class="glyphicon glyphicon-plus"></span> Write a review</button>');
        $('#reviews-header').prepend(button);
    };

    ui.check_review = function(event) {
        event.preventDefault();
        if ($('#review-form-preview').text().length < 200) {
            alert('Your review must at least 200 characters long.');
            return;
        }

        var form = $("#review-edit-form");

        var url = Routing.generate('card_review_post');
        if (app.user.data.review_id) {
            url = Routing.generate('card_review_edit');
        }

        var data = $(this).serialize();

        $.ajax(url, {
            data: data,
            type: 'POST',
            dataType: 'json',
            success: function(data, textStatus, jqXHR) {
                ui.notify(form, 'success', "Your review has been posted. It will appear on the site in a few minutes.");
                form.remove();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
                ui.notify(form, 'danger', jqXHR.responseJSON.message);
            }
        });
    };

    ui.notify = function(form, type, message) {
        var alert = $('<div class="alert" role="alert"></div>').addClass('alert-' + type).text(message);
        $(form).after(alert);
    };

    /**
     * The user has clicked on the button to write a new review or edit the current one
     * This function adds a review form to the page
     */
    ui.write_review_open = function() {
        var button = this;
        $(button).remove();

        /**
         * Display the form
         */
        var form = $("#review-edit-form");
        form.append('<div><div class="form-group">'
            + '<textarea id="review-form-text" class="form-control" rows="20" name="review" placeholder="Write your analysis of the card, in at least 200 characters. You can write a number of card reviews equal to your reputation. This is not a place for questions or comments. Type # to enter a card name. Type $ to enter a symbol."></textarea>'
            + '</div><div class="well text-muted" id="review-form-preview"><small>Preview. Look <a href="http://daringfireball.net/projects/markdown/dingus">here</a> for a Markdown syntax reference.</small></div>'
            + '<button type="submit" class="btn btn-success">Submit review</button></div>');
        form.on('submit', ui.check_review);

        /**
         * Setup the Markdown preview and Textcomplete shortcuts
         */
        app.markdown.setup('#review-form-text', '#review-form-preview');
        app.textcomplete.setup('#review-form-text');

        /**
         * If the User already wrote a review, we fill the form with the current values
         */
        if (app.user.data.review_id) {
            $('#review-form-text').val(app.user.data.review_text).trigger('keyup');
        }
    };

    /**
     * The user has clicked on "Add a comment"
     * Thsi function replace that button with a one-line for to input and submit the comment
     */
    ui.write_comment = function(event) {
        event.preventDefault();
        $(this).replaceWith('<div class="input-group"><input type="text" class="form-control" name="comment" placeholder="Your comment"><span class="input-group-btn"><button class="btn btn-primary" type="submit">Post</button></span></div>');
    };

    /**
     * The user has clicked on "Submit the comment"
     * @param event
     */
    ui.form_comment_submit = function(event) {
        event.preventDefault();
        var form = $(this);
        if (form.data('submitted')) return;
        form.data('submitted', true);
        $.ajax(form.attr('action'), {
            data: form.serialize(),
            type: 'POST',
            dataType: 'json',
            success: function(data, textStatus, jqXHR) {
                ui.notify(form, 'success', "Your comment has been posted. It will appear on the site in a few minutes.");
                form.remove();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
                ui.notify(form, 'danger', jqXHR.responseBody.message);
            }
        });
    };

    ui.like_review = function(event) {
        event.preventDefault();
        var obj = $(this);
        var review_id = obj.closest('article.review').data('id');
        $.post(Routing.generate('card_review_like'), {
            id: review_id
        }, function(data, textStatus, jqXHR) {
            obj.find('.num').text(jqXHR.responseJSON.nbVotes);
        });
    };

    ui.setup_art_selector = function() {
        if (typeof window._cardPacks === 'undefined' || !window._cardCode) return;
        if (!(app.user.data && app.user.data.id)) return;

        // All packs this card appears in — used for the owned-count table.
        var allPacks = window._cardPacks || [];
        if (allPacks.length < 1) return;

        var counts      = app.data.owned_pack_counts || {};
        var currentPack = window._cardCurrentPack || null;
        var cardUrl     = window._cardUrl || window.location.pathname;
        // With more than one printing, a row click reloads the page on that set
        // (same behaviour the printings list under the card info used to provide).
        var multiPack   = allPacks.length > 1;

        var container = $('<div style="margin-top:10px"></div>');
        $('<small class="text-muted">Printing (owned):</small>').appendTo(container);
        var tableStyle = 'margin-bottom:0;margin-top:4px' + (multiPack ? ';cursor:pointer' : '');
        var table = $('<table class="table table-condensed table-hover table-striped" style="' + tableStyle + '"><thead><tr><th>Set</th><th style="text-align:center">Owned</th></tr></thead></table>').appendTo(container);
        var tbody = $('<tbody></tbody>').appendTo(table);

        allPacks.forEach(function(p) {
            var selected = (p.pack_code === currentPack);
            var owned = (counts[p.pack_code] || 0) * (p.quantity || 0);
            var row = $('<tr class="' + (selected ? 'info' : '') + '">'
                + '<td>' + p.pack_name + ' #' + p.position + '</td>'
                + '<td style="text-align:center">' + owned + '</td>'
                + '</tr>');
            if (multiPack && !selected) {
                row.on('click', function() {
                    window.location.href = cardUrl + '?pack=' + p.pack_code;
                });
            }
            row.appendTo(tbody);
        });

        $('#card-art-selector').append(container);
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
        app.user.loaded.done(function() {
            if (app.user.data.review_id) {
                ui.setup_edit(app.user.data.review_id);
            } else {
                ui.setup_write();
            }
        });

        $(window.document).on('click', '.btn-write-comment', ui.write_comment);
        $(window.document).on('click', '.social-icon-like', ui.like_review);
        $(window.document).on('click', '#review-button', ui.write_review_open);
        $(window.document).on('submit', 'form.form-comment', ui.form_comment_submit);
    };

})(app.ui, jQuery);
