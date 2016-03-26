(function app_card_modal(card_modal, $) {

    var modal = null;

    /**
     * @memberOf card_modal
     */
    card_modal.display_modal = function(event, element) {
        event.preventDefault();
        $(element).qtip('destroy', true);
        fill_modal($(element).data('code'));
    };

    /**
     * @memberOf card_modal
     */
    card_modal.typeahead = function(event, card) {
        fill_modal(card.code);
        $('#cardModal').modal('show');
    };

    card_modal.updateModal = function() {
        var modal = $('#cardModal');
        var code = modal.data('code');

        fill_modal(code);
    };

    function fill_modal(code) {
        var card = app.data.cards.findById(code);
        var modal = $('#cardModal');

        if (!card) {
            return;
        }

        modal.data('code', code);
        modal.find('.card-modal-link').attr('href', card.url);
        modal.find('h3.modal-title').html(app.format.name(card));
        modal.find('.modal-image').html('<img class="img-responsive" src="' + card.imagesrc + '">');
        modal.find('.modal-info').html(
            '<div class="card-info">' + app.format.info(card) + '</div>' +
            '<div><small>' + app.format.pack_sphere(card) + '</small></div>' +
            '<div class="card-text"><small>' + app.format.text(card) + '</small></div>'
        );

        if (!app.ui.deckedit) {
            return;
        }

        var row = modal.find('.modal-qty-row').empty();
        var container = $('<div class="modal-qty-container"><label>Main Deck</label></div>').appendTo(row);
        var qtyelt = $('<div class="btn-group" data-toggle="buttons"></div>').appendTo(container);

        for (var i = 0; i <= card.maxqty; i++) {
            var label = $('<label class="btn btn-default"><input type="radio" name="qty" value="' + i + '">' + i + '</label>');

            if (i == card.indeck) {
                label.addClass('active');
            }

            label.appendTo(qtyelt);
        }

        if (card.maxqty > 1) {
            container = $('<div class="modal-move-card-container hidden-xs"><label>&#160;</label></div>').appendTo(row);
            qtyelt = $('<div class="btn-group" data-toggle="buttons"></div>').appendTo(container);

            var left = $('<label class="btn btn-default" data-direction="left"><span class="fa fa-angle-left"</label>').appendTo(qtyelt);
            var right = $('<label class="btn btn-default" data-direction="right"><span class="fa fa-angle-right"</label>').appendTo(qtyelt);

            if (card.insidedeck == 0) {
                left.addClass('disabled');
            }

            if (card.indeck == 0) {
                right.addClass('disabled');
            }
        }

        container = $('<div class="modal-side-qty-container"><label>Sideboard</label></div>').appendTo(row);
        qtyelt = $('<div class="btn-group" data-toggle="buttons"></div>').appendTo(container);

        for (var i = 0; i <= card.maxqty; i++) {
            var label = $('<label class="btn btn-default"><input type="radio" name="side-qty" value="' + i + '">' + i + '</label>');

            if (i == card.insidedeck) {
                label.addClass('active');
            }

            label.appendTo(qtyelt);
        }
    }

    $(document).ready(function () {
        $('body').on({
            click: function (event) {
                var element = $(this);

                if (event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
                    event.stopPropagation();
                    return;
                }

                card_modal.display_modal(event, element);
            }
        }, '.card');
    });

})(app.card_modal = {}, jQuery);
