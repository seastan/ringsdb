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
            // Parse owned_packs into a per-pack-id owned COUNT. Tokens are
            // "id", "id:count", or legacy core "1-2"/"1-3" (each = +1 copy).
            var ownedCountById = {};
            var hasCollection = !!(app.user.data && app.user.data.owned_packs);
            if (hasCollection) {
                _.forEach(app.user.data.owned_packs.split(','), function(token) {
                    var t = ('' + token).trim(), m;
                    if ((m = t.match(/^(\d+):(\d+)$/))) {
                        ownedCountById[m[1]] = (ownedCountById[m[1]] || 0) + parseInt(m[2], 10);
                    } else if ((m = t.match(/^(\d+)(?:-\d+)?$/))) {
                        ownedCountById[m[1]] = (ownedCountById[m[1]] || 0) + 1;
                    }
                });
            }

            // Owned count per pack code (exposed for per-art counts in the card modal).
            // No collection set => own one of each pack (and everything abundantly below).
            var ownedCountByCode = {};
            app.data.packs.find().forEach(function(pack) {
                var count = hasCollection ? (ownedCountById[pack.id] || 0) : 1;
                ownedCountByCode[pack.code] = count;
                app.data.packs.updateById(pack.code, { owned: count > 0 });
            });
            app.data.owned_pack_counts = ownedCountByCode;

            // Per card: owned_copies = sum over its printings of
            // (count owned of that pack * copies of the card in that pack).
            // No collection => treat as owning everything abundantly.
            app.data.cards.find().forEach(function(card) {
                var ownedCopies;
                if (!hasCollection) {
                    ownedCopies = 999;
                } else {
                    ownedCopies = 0;
                    _.forEach(card.packs || [], function(pr) {
                        ownedCopies += (ownedCountByCode[pr.pack_code] || 0) * (pr.quantity || 0);
                    });
                }
                app.data.cards.updateById(card.code, {
                    owned_copies: ownedCopies,
                    owned: ownedCopies > 0
                });
            });

            // Apply the user's preferred art: swap card.imagesrc to the chosen printing.
            var artPrefs = {};
            if (app.user.data && app.user.data.art_preferences) {
                try { artPrefs = JSON.parse(app.user.data.art_preferences) || {}; } catch (e) { artPrefs = {}; }
            }
            app.data.art_preferences = artPrefs;
            _.forEach(artPrefs, function(packCode, code) {
                var card = app.data.cards.findById(code);
                if (!card || !card.packs) {
                    return;
                }
                var pr = _.find(card.packs, function(p) { return p.pack_code === packCode; });
                if (pr && pr.imagesrc) {
                    app.data.cards.updateById(code, { imagesrc: pr.imagesrc });
                }
            });

            // Deckbuilder per-card max quantities depend on ownership.
            if (app.ui && $.isFunction(app.ui.set_max_qty)) {
                app.ui.set_max_qty();
            }
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
