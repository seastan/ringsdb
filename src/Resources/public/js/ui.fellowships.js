(function(ui, $) {

    ui.fellowships = [];

    ui.confirm_delete = function(event) {
        var tr = $(this).closest('tr');
        var fellowship_id = tr.data('id');
        var fellowship_name = tr.find('.fellowship-name').text();

        $('#delete-fellowship-name').text(fellowship_name);
        $('#delete-fellowship-id').val(fellowship_id);
        $('#deleteModal').modal('show');
    };

    ui.confirm_delete_all = function(ids) {
        $('#delete-fellowship-list-id').val(ids.join('-'));
        $('#deleteListModal').modal('show');
    };

    ui.create_quest = function(ids) {
        var tr = $(this).closest('tr');
        var decks = tr.find('.fellowship-heroes');

        var data = {};
        var i = 1;
        decks.each(function() {
            var id = $(this).data('id');
            var pub = $(this).data('published');
            if (id) {
                data['deck' + i + '_id'] = id;
                if (pub) {
                    data['p' + i] = pub;
                }
            }
            i++;
        });

        location.href = Routing.generate('questlog_new', data);
    };

    ui.do_action_selection = function(event) {
        event.stopPropagation();

        var action_id = $(this).attr('id');
        var ids = $('.list-fellowships input:checked').map(function(index, elt) {
            return $(elt).closest('tr').data('id');
        }).get();

        if (!action_id || !ids.length) {
            return;
        }

        switch (action_id) {
            case 'btn-delete-selected':
                ui.confirm_delete_all(ids);
                break;
        }

        return false;
    };

    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        $('#fellowships')
            .on('click', 'button.btn-delete-fellowship', ui.confirm_delete)
            .on('click', 'button.btn-log-quest', ui.create_quest)
            .on('click', 'input[type=checkbox]', function(event) {
                var checked = $(this).closest('tbody').find('input[type=checkbox]:checked');

                var button = $('#btn-group-selection button');
                if (checked.size()) {
                    button.removeClass('btn-default').addClass('btn-primary')
                } else {
                    button.addClass('btn-default').removeClass('btn-primary')
                }
            });

        $('#btn-group-selection').on('click', 'button[id], a[id]', ui.do_action_selection);
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
