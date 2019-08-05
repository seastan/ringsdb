(function app_textcomplete(textcomplete, $) {

    var icons = 'spirit tactics lore leadership neutral baggins fellowship unique threat willpower attack defense health hero ally attachment event player-side-quest contract treasure'.split(' ');

    /**
     * options: cards, icons, users
     */
    textcomplete.setup = function setup(textarea, options) {
        options = _.extend({cards: true, icons: true, users: false}, options);

        var actions = [];

        if (options.cards) {
            actions.push({
                match: /\B#([\-+\w\u00E0-\u00FC]*)$/,
                search: function(term, callback) {
                    term = app.data.get_searchable_string(term);
                    var regexp = new RegExp('\\b' + term, 'i');

                    callback(app.data.cards.find({
                        s_name: regexp
                    }));
                },
                template: function(value) {
                    return '<span style="display: inline-block; width: 2em; text-align: center" class="icon-' + value.sphere_code + '"></span> ' + value.name + ' <small><i>' + value.pack_name + '</i></small>';
                },
                replace: function(value) {
                    return '[' + value.name + '](' + Routing.generate('cards_zoom', { card_code: value.code }) + ')';
                },
                index: 1
            })
        }

        if (options.icons) {
            actions.push({
                match: /\$([\-+\w]*)$/,
                search: function(term, callback) {
                    var regexp = new RegExp('^' + term, 'i');
                    callback(_.filter(icons,
                        function(symbol) {
                            return regexp.test(symbol);
                        }
                    ));
                },
                template: function(value) {
                    return '<span style="display: inline-block; width: 2em; text-align: center" class="icon-' + value + '"></span> ' + value;
                },
                replace: function(value) {
                    return '<span class="icon-' + value + '"></span>';
                },
                index: 1
            });
        }

        if (options.users) {
            actions.push({
                match: /\B@([\-+\w\u00E0-\u00FC]*)$/,
                search: function(term, callback) {
                    var regexp = new RegExp('^' + term, 'i');
                    callback($.grep(options.users, function(user) {
                        return regexp.test(user);
                    }));
                },
                template: function(value) {
                    return value;
                },
                replace: function(value) {
                    return '`@' + value + '`';
                },
                index: 1
            });
        }

        $(textarea).textcomplete(actions);
    }

})(app.textcomplete = {}, jQuery);
