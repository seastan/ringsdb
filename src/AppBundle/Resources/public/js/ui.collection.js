(function (ui, $) {

    ui.init_select_buttons = function() {
        $('#owned_packs')
            .on('click', '.select-all', function(e) {
                var cycle = $(this).closest('.cycle');
                cycle.find('.pack-qty').val(1);
                ui.update_pack_counting(cycle);
            })
            .on('click', '.select-none', function(e) {
                var cycle = $(this).closest('.cycle');
                cycle.find('.pack-qty').val(0);
                ui.update_pack_counting(cycle);
            })
            .on('change input', '.pack-qty', function() {
                ui.update_pack_counting($(this).closest('.cycle'));
            });

        $('#owned_packs').show();
    };

    ui.init_pack_counting = function() {
        $('.cycle').each(function() {
            ui.update_pack_counting(this);
        });
    };

    ui.update_pack_counting = function(el) {
        var cycle = $(el);
        var owned = 0;
        var total = 0;
        cycle.find('.pack-qty').each(function() {
            if ((parseInt($(this).val(), 10) || 0) > 0) {
                owned++;
            }
            total++;
        });

        cycle.find('.pack-count').text(owned + ' / ' + total);
    };

    ui.update_selected_packs = function() {
        var packs = [];
        $('#owned_packs .pack-qty').each(function() {
            var count = parseInt($(this).val(), 10) || 0;
            if (count > 0) {
                packs.push($(this).data('id') + ':' + count);
            }
        });
        $('#selected-packs').val(packs.join(','));
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        ui.init_select_buttons();
        ui.init_pack_counting();
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function() {
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function() {
    };
})(app.ui, jQuery);
