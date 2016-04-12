(function(ui, $) {

    ui.questlogs = [];

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
                $('#scenario-list').removeClass('easy normal nightmare').addClass(action_id);
                break;
        }
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
