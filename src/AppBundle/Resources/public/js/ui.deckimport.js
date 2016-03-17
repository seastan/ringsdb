(function ui_deckimport(ui, $) {


    ui.on_content_change = function on_content_change(event) {
        var text = $(content).val();
        var slots = {};

        text = text.replace(/[\u2018\u2019]/g, "'");

        text.match(ui.reg).forEach(function(token) {
            var qty = 1;
            var name = token.trim();
            var pack;
            var card;

            if (token[0] === '(') {
                return;
            }

            // Extracting quantity:
            var qtyMatch = name.match(/(x\d+|\d+x)/);
            if (qtyMatch) {
                var m = qtyMatch[1]
                qty = parseInt(m.replace('x', ''), 10);
                name = name.replace(m, '').trim();
            }

            if (name.match(/^([^\(]*)\(?([^\)]*)\)?/)) {
                // Match Card Name (Pack Name)
                name = RegExp.$1.trim();
                pack = RegExp.$2.trim();
            }

            var name_regexp = new RegExp('^' + ui.sanitize_characters(name) + '$', 'i');

            if (pack) {
                var pack_regexp = new RegExp('^' + ui.sanitize_characters(pack) + '$', 'i');
                card = app.data.cards.findOne({ name: name_regexp, pack_name: pack_regexp }) || app.data.cards.findOne({ name: name_regexp, pack_code: pack_regexp });
            }

            if (!card) {
                if (pack) {
                    console.log('Pack not found: ' + pack);
                }

                card = app.data.cards.findOne({ name: name_regexp });
            }

            if (card) {
                slots[card.code] = qty;
            } else {
                console.log('rejecting string [' + name + ']');
            }
        });

        app.deck.init({ slots: slots });
        app.deck.display('#deck');

        $('input[name=content]').val(app.deck.get_json());
    };

    ui.sanitize_characters = function sanitize_characters(chars) {
        return chars.replace(/[[\](){}?*+^$\\.|]/g, '\\$&');
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
        $('#content').change(ui.on_content_change);
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function on_data_loaded() {
        var characters = _.unique(_.map(app.data.cards.find(), function(e) {
            return e.name + e.pack_name;
        }).join('').split('').sort()).join('');

        ui.reg = new RegExp('\\(?[\\d\\(\\)' + ui.sanitize_characters(characters) + ']+\\)?', 'g');
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
    };


})(app.ui, jQuery);
