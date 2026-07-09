(function custom_pack_form($) {
    'use strict';

    var addedCodes = {};

    function findMatches(q, cb) {
        var name = app.data.get_searchable_string(q);
        var re1 = new RegExp('^' + name, 'i');
        var re2 = new RegExp('.+' + name, 'i');
        var starts = app.data.cards.find({ s_name: re1 });
        var contains = app.data.cards.find({ s_name: re2 });
        cb(starts.concat(contains));
    }

    function addCardRow(card, qty) {
        if (addedCodes[card.code]) {
            return;
        }
        addedCodes[card.code] = true;

        var defaultQty = (card.type_code === 'hero') ? 1 : 3;
        var quantity = qty !== undefined ? qty : defaultQty;

        var row = $('<div class="custom-pack-card-row"></div>')
            .attr('data-code', card.code);

        $('<input type="number" class="card-qty" min="1" max="9">')
            .val(quantity)
            .appendTo(row);

        var sphere = card.sphere_code || 'neutral';
        $('<div class="card-label"><div class="card-tip fg-' + sphere + '" data-code="' + card.code + '" style="display:inline">'
            + '<span class="icon-fw icon-' + sphere + '"></span> '
            + '<strong>' + app.data.display_name(card) + '</strong>'
            + (card.type_name ? ' <small><i>' + card.type_name + '</i></small>' : '')
            + '</div></div>')
            .appendTo(row);

        $('<button type="button" class="btn btn-xs btn-danger remove-card-btn">')
            .html('<span class="fa fa-times"></span>')
            .on('click', function() {
                delete addedCodes[card.code];
                row.remove();
            })
            .appendTo(row);

        row.appendTo('#custom-pack-cards');
    }

    function serializeCards() {
        var entries = [];
        $('#custom-pack-cards .custom-pack-card-row').each(function() {
            var code = $(this).data('code');
            var qty = parseInt($(this).find('.card-qty').val(), 10) || 1;
            entries.push({ card_code: String(code), quantity: qty });
        });
        $('#cards-json').val(JSON.stringify(entries));
    }

    $(document).on('start.app', function() {
        if (!$('#custom-pack-form').length) {
            return;
        }

        // Mark existing card rows (edit mode) so we don't re-add them.
        $('#custom-pack-cards .custom-pack-card-row').each(function() {
            var code = String($(this).data('code'));
            addedCodes[code] = true;
            var self = $(this);
            self.find('.remove-card-btn').on('click', function() {
                delete addedCodes[code];
                self.remove();
            });
        });

        $('#card-search').typeahead({
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
                    return $('<div class="fg-' + card.sphere_code + '"><span class="icon-fw icon-' + card.sphere_code + '"></span> <strong>' + app.data.display_name(card) + '</strong> <small><i>' + card.type_name + '</i></small></div>');
                }
            }
        }).on('typeahead:selected typeahead:autocompleted', function(event, card) {
            addCardRow(card);
            $(this).typeahead('val', '');
        });

        $('#custom-pack-form').on('submit', function() {
            serializeCards();
        });
    });

}(jQuery));
