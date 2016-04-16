(function(ui, $) {

    var Config = null;
    ui.questlogs = [];

    /**
     * reads ui configuration from localStorage
     * @memberOf ui
     */
    ui.read_config_from_storage = function() {
        if (localStorage) {
            var stored = localStorage.getItem('ui.questlogs.config');

            if (stored) {
                Config = JSON.parse(stored);
            }
        }

        Config = _.extend({
            'owned-quests-only': true,
            'played-quests-only': false
        }, Config || {});
    };

    /**
     * write ui configuration to localStorage
     * @memberOf ui
     */
    ui.write_config_to_storage = function() {
        if (localStorage) {
            localStorage.setItem('ui.questlogs.config', JSON.stringify(Config));
        }
    };

    /**
     * inits the state of config buttons
     * @memberOf ui
     */
    ui.init_config_buttons = function() {
        // checkbox
        ['owned-quests-only', 'played-quests-only'].forEach(function(checkbox) {
            if (Config[checkbox]) {
                $('input[name=' + checkbox + ']').prop('checked', true);
            }
        })
    };

    /**
     * @memberOf ui
     * @param event
     */
    ui.on_config_change = function() {
        var name = $(this).attr('name');
        Config[name] = $(this).prop('checked');

        ui.write_config_to_storage();

        switch (name) {
            case 'owned-quests-only':
            case 'played-quests-only':
                ui.update_quest_display();
                break;
            default:
        }
    };

    /**
     * sets up event handlers ; dataloaded not fired yet
     * @memberOf ui
     */
    ui.setup_event_handlers = function() {
        $('#btn-group-selection').on('click', 'a[data-action]', ui.do_action_selection);
        $('body').on('click', 'button[data-action], label[data-action]', ui.do_action);

        $('#questlogs')
            .on('click', 'input[type=checkbox]', function(event) {
                var checked = $(this).closest('tbody').find('input[type=checkbox]:checked');

                var button = $('#btn-group-selection label');
                if (checked.size()) {
                    button.removeClass('btn-default').addClass('btn-primary')
                } else {
                    button.addClass('btn-default').removeClass('btn-primary')
                }
            });

        $('#config-options').on('change', 'input', ui.on_config_change);
    };

    ui.do_action_selection = function(event) {
        var action_id = $(this).attr('data-action');

        var ids = $('.list-questlogs input:checked').map(function(index, elt) {
            return $(elt).closest('tr').data('id');
        }).get();

        if (!action_id || !ids.length) {
            return;
        }

        switch (action_id) {
            case 'delete-selected':
                ui.confirm_delete_all(ids);
                break;
        }
    };

    ui.do_action = function(event) {
        var action_id = $(this).attr('data-action');

        if (!action_id) {
            return;
        }

        switch (action_id) {
            case 'delete-questlog':
                ui.confirm_delete.call(this);
                break;

            case 'easy':
            case 'normal':
            case 'nightmare':
                $(this).addClass('active').siblings('label').removeClass('active');
                var list = $('#scenario-list').removeClass('easy normal nightmare').addClass(action_id);

                var lastH5 = null;
                var visible = false;
                list.children().each(function() {
                    var el = $(this);

                    if (this.tagName.toLowerCase() == 'h5') {
                        if (lastH5) {
                            if (visible) {
                                lastH5.show();
                            } else {
                                lastH5.hide();
                            }
                        }

                        lastH5 = el;
                        visible = false;
                    } else {
                        if (el.is(':visible')) {
                            visible = true;
                        }
                    }
                });

                if (visible) {
                    lastH5.show();
                } else {
                    lastH5.hide();
                }

                break;
        }
    };

    ui.update_quest_display = function() {
        var list = $('#scenario-list');
        var diff_selector = $('#difficulty-selector');

        list.children('li').removeClass('hidden');

        if (Config['owned-quests-only']) {
            var packs = app.data.packs.find({ owned: false });

            packs.forEach(function(pack) {
                list.children('li[data-pack="' + pack.id + '"]').addClass('hidden');
            });
        }

        if (Config['played-quests-only']) {
            list.children('li.not-beaten').addClass('hidden');
        }

        ['easy', 'normal', 'nightmare'].forEach(function(difficulty) {
            var total = list.find('li.scenario-' + difficulty + ':not(.hidden)').size();
            var beaten = list.find('li.scenario-' + difficulty + ':not(.hidden) a').size();
            diff_selector.find('label[data-action="' + difficulty + '"] strong').text('(' + beaten + '/' + total + ')');
        });

        list.removeClass('hidden');
        diff_selector.removeClass('hidden').find('.active').trigger('click');
        $('#config-options').removeClass('hidden');
    };

    ui.confirm_delete = function() {
        var tr = $(this).closest('tr');
        var questlog_id = tr.data('id');
        var questlog_name = tr.find('.questlog-name').text();

        $('#delete-questlog-name').text(questlog_name);
        $('#delete-questlog-id').val(questlog_id);
        $('#deleteModal').modal('show');
    };

    ui.confirm_delete_all = function(ids) {
        $('#delete-questlog-list-id').val(ids.join('-'));
        $('#deleteListModal').modal('show');
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        ui.setup_event_handlers();
        ui.init_config_buttons();
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
        ui.update_quest_display();
    };

    ui.read_config_from_storage();

})(app.ui, jQuery);
