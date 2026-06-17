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
        card_modal.build_art_selector(card, modal);

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

            if (card.insideboard == 0) {
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

            if (i == card.insideboard) {
                label.addClass('active');
            }

            label.appendTo(qtyelt);
        }
    }

    /**
     * Builds the art / printing selector under the modal image when a card has
     * more than one distinct printing art. Each option shows how many copies of
     * THAT art the user owns. Selecting one persists the preference.
     * @memberOf card_modal
     */
    card_modal.build_art_selector = function(card, modal) {
        // distinct printings that have art, keyed by image_code
        var seen = {}, arts = [];
        _.forEach(card.packs || [], function(p) {
            if (p.imagesrc && !seen[p.image_code]) {
                seen[p.image_code] = 1;
                arts.push(p);
            }
        });

        if (arts.length < 2 || !(app.user.data && app.user.data.id)) {
            return;
        }

        var prefs = app.data.art_preferences || {};
        var current = prefs[card.code]; // preferred pack_code, or undefined => canonical
        var counts = app.data.owned_pack_counts || {};

        var container = $('<div class="modal-art-selector" style="margin-top:8px"></div>');
        $('<small class="text-muted">Art / printing (copies you own):</small>').appendTo(container);
        var group = $('<div style="margin-top:4px"></div>').appendTo(container);

        arts.forEach(function(p) {
            var isCanonical = (p.image_code === card.code);
            var selected = current ? (current === p.pack_code) : isCanonical;
            var owned = (counts[p.pack_code] || 0) * (p.quantity || 0);
            var label = $('<label class="btn btn-xs btn-default' + (selected ? ' active' : '') + '" style="margin:2px" title="' + p.pack_name + '">'
                + p.pack_code + ' <span class="text-muted">(' + owned + ')</span></label>');
            label.on('click', function() {
                card_modal.set_art(card.code, isCanonical ? '' : p.pack_code, p.imagesrc);
            });
            label.appendTo(group);
        });

        container.appendTo(modal.find('.modal-image'));
    };

    /**
     * Persists and applies an art preference, then refreshes the modal.
     * @memberOf card_modal
     */
    card_modal.set_art = function(code, packCode, imagesrc) {
        $.post(Routing.generate('collection_save_art'), { card_code: code, pack_code: packCode || 'default' });

        var prefs = app.data.art_preferences || (app.data.art_preferences = {});
        if (packCode) {
            prefs[code] = packCode;
        } else {
            delete prefs[code];
        }
        app.data.cards.updateById(code, { imagesrc: imagesrc });
        card_modal.updateModal();
    };

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
