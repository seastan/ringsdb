(function ui_review(ui, $) {

    /**
     * The user has clicked on "Add a comment"
     * Thsi function replace that button with a one-line for to input and submit the comment
     */
    ui.write_comment = function write_comment(event) {
        event.preventDefault();
        $(this).replaceWith('<div class="input-group"><input type="text" class="form-control" name="comment" placeholder="Your comment"><span class="input-group-btn"><button class="btn btn-primary" type="submit">Post</button></span></div>');
    };

    ui.notify = function notify(form, type, message) {
        var alert = $('<div class="alert" role="alert"></div>').addClass('alert-' + type).text(message);
        $(form).after(alert);
    };

    /**
     * The user has clicked on "Submit the comment"
     * @param event
     */
    ui.form_comment_submit = function form_comment_submit(event) {
        event.preventDefault();
        var form = $(this);
        if (form.data('submitted')) {
            return;
        }

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
                console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
                ui.notify(form, 'danger', jqXHR.responseBody.message);
            }
        });
    };

    ui.like_review = function like_review(event) {
        event.preventDefault();
        var obj = $(this);
        var review_id = obj.closest('article.review').data('id');
        $.post(Routing.generate('card_review_like'), {
            id : review_id
        }, function(data, textStatus, jqXHR) {
            obj.find('.num').text(jqXHR.responseJSON.nbVotes);
        });
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
        $(window.document).on('click', '.btn-write-comment', ui.write_comment);
        $(window.document).on('click', '.social-icon-like', ui.like_review);
        $(window.document).on('submit', 'form.form-comment', ui.form_comment_submit);
    };

})(app.ui, jQuery);
