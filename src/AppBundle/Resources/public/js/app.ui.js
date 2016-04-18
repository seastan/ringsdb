(function ui_deck(ui, $) {

    var dom_loaded = new $.Deferred();
    var data_loaded = new $.Deferred();

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function on_dom_loaded() {
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function on_data_loaded() {
    };

    /**
     * called when both the DOM and the app data have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function on_all_loaded() {
    };

    ui.insert_alert_message = function ui_insert_alert_message(type, message) {
        var alert = $('<div class="alert hidden-print" role="alert"></div>').addClass('alert-' + type).append(message);
        $('#wrapper').find('>div.container:first').prepend(alert);
    };

    $.fn.ignore = function(sel) {
        return this.clone().find(sel).remove().end();
    };

    $(document).ready(function() {
        console.log('ui.on_dom_loaded');

        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            $.fn.tooltip = function() {
                return this;
            };

            try { // prevent crash on browsers not supporting DOM styleSheets properly
                for (var si in document.styleSheets) {
                    var styleSheet = document.styleSheets[si];
                    if (!styleSheet.rules) {
                        continue;
                    }

                    for (var ri = styleSheet.rules.length - 1; ri >= 0; ri--) {
                        if (!styleSheet.rules[ri].selectorText) {
                            continue;
                        }

                        if (styleSheet.rules[ri].selectorText.match('(btn-default).*(:hover|:active)')) {
                            var texts = styleSheet.rules[ri].selectorText.split(',');
                            var selector = [];
                            for (var m = 0; m < texts.length; m++) {
                                if (!texts[m].match('(btn-default).*(:hover|:active)')) {
                                    selector.push(texts[m]);
                                }
                            }
                            if (selector.length) {
                                styleSheet.rules[ri].selectorText = selector.join(', ');
                            } else {
                                styleSheet.deleteRule(ri);
                            }
                        }
                    }
                }
            } catch (ex) {}
        } else {
            $('[data-toggle="tooltip"]').tooltip();
        }

        $('time').each(function(index, element) {
            var datetime = moment($(element).attr('datetime'));

            $(element).html(datetime.fromNow());
            $(element).attr('title', datetime.format('LLLL'));
        });

        if ($.isFunction(ui.on_dom_loaded)) {
            ui.on_dom_loaded();
        }

        dom_loaded.resolve();
    });

    $(document).on('data.app', function() {
        console.log('ui.on_data_loaded');

        if ($.isFunction(ui.on_data_loaded)) {
            ui.on_data_loaded();
        }

        data_loaded.resolve();
    });

    $(document).on('start.app', function() {
        console.log('ui.on_all_loaded');

        app.user.loaded.always(function() {
            app.data.packs.update({}, {
                owned: true
            });

            if (app.user.data && app.user.data.owned_packs) {
                var packs = app.user.data.owned_packs.split(',');
                var nPacks = [];
                _.forEach(packs, function(str) {
                    nPacks.push(parseInt(str, 10));
                });

                app.data.packs.update({
                    'id': {
                        '$nin': nPacks
                    }
                }, {
                    owned: false
                });
            }

            app.data.packs.find().forEach(function(pack) {
                app.data.cards.update({
                    'pack_code': pack.code
                }, {
                    owned: pack.owned
                });
            });
        });

        if ($.isFunction(ui.on_all_loaded)) {
            ui.on_all_loaded();
        }

        $('abbr').each(function(index, element) {
            var title;

            switch ($(element).text().toLowerCase()) {
                // TODO: Add keywords here
                //case 'renown':
                //    title = "After you win a challenge in which this character is participating, he may gain 1 power.";
                //    break;
            }

            if (title) {
                $(element).attr('title', title).tooltip();
            }
        })
    });

    $.when(dom_loaded, data_loaded).done(function() {
        setTimeout(function() {
            $(document).trigger('start.app');
        }, 0);
    });

})(app.ui = {}, jQuery);
