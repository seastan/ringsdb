(function app_suggestions(suggestions, $) {

    suggestions.codesFromindex = [];
    suggestions.matrix = [];
    suggestions.indexFromCodes = {};
    suggestions.current = [];
    suggestions.exclusions = [];
    suggestions.number = 3;
    suggestions.isLoaded = false;

    /**
     * @memberOf suggestions
     */
    suggestions.query = function query() {
        suggestions.promise = $.ajax('/suggestions.json', {
            dataType: 'json',
            success: function(data) {
                suggestions.codesFromindex = data.index;
                suggestions.matrix = data.matrix;

                // reconstitute the full matrix from the lower half matrix
                for (var i = 0; i < suggestions.matrix.length; i++) {
                    for (var j = i; j < suggestions.matrix.length; j++) {
                        suggestions.matrix[i][j] = suggestions.matrix[j][i];
                    }
                }

                for (var i = 0; i < suggestions.codesFromindex.length; i++) {
                    suggestions.indexFromCodes[suggestions.codesFromindex[i]] = i;
                }

                suggestions.isLoaded = true;
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('[' + moment().format('YYYY-MM-DD HH:mm:ss') + '] Error on ' + this.url, textStatus, errorThrown);
            }
        });
        suggestions.promise.done(suggestions.compute);
    };

    /**
     * @memberOf suggestions
     */
    suggestions.compute = function compute() {
        if (suggestions.number) {

            // init current suggestions
            suggestions.codesFromindex.forEach(function(code, index) {
                suggestions.current[index] = {
                    code: code,
                    proba: 0
                };
            });

            // find used cards
            var indexes = _.pluck(app.data.cards.find({ indeck: { '$gt': 0 } }), 'code').map(function(code) {
                return suggestions.indexFromCodes[code];
            });

            // add suggestions of all used cards
            indexes.forEach(function(i) {
                if (suggestions.matrix[i]) {
                    suggestions.matrix[i].forEach(function(value, j) {
                        suggestions.current[j].proba += (value || 0);
                    });
                }
            });

            // remove suggestions of already used cards
            indexes.forEach(function(i) {
                if (suggestions.current[i]) {
                    suggestions.current[i].proba = 0;
                }
            });

            // remove suggestions of heroes
            _.pluck(app.data.cards.find({ type_code: 'hero' }), 'code').map(function(code) {
                return suggestions.indexFromCodes[code];
            }).forEach(function(i) {
                if (suggestions.current[i]) {
                    suggestions.current[i].proba = 0;
                }
            });

            // remove suggestions of excluded cards
            suggestions.exclusions.forEach(function(i) {
                suggestions.current[i].proba = 0;
            });

            // sort suggestions
            suggestions.current.sort(function(a, b) {
                if (b.proba == a.proba) {
                    return a.code > b.code ? 1 : -1;
                }

                return (b.proba - a.proba);
            });

        }
        suggestions.show();
    };

    /**
     * @memberOf suggestions
     */
    suggestions.show = function show() {
        var table = $('#table-suggestions');
        var tbody = table.children('tbody').empty();

        if (!suggestions.number && table.is(':visible')) {
            table.hide();
            return;
        }

        if (suggestions.number && !table.is(':visible')) {
            table.show();
        }

        var nb = 0;
        for (var i = 0; i < suggestions.current.length; i++) {
            if (!suggestions.current[i].proba) {
                break;
            }

            var card = app.data.cards.findById(suggestions.current[i].code);
            if (card.owned) {
                suggestions.div(card).on('click', 'button.close', suggestions.exclude.bind(this, card.code)).appendTo(tbody);

                if (++nb == suggestions.number) {
                    break;
                }
            }
        }
    };

    /**
     * @memberOf suggestions
     */
    suggestions.template = _.template(
        '<tr class="card-container" data-code="<%= code %>">' +
        '<td><button type="button" class="close"><span aria-hidden="true">&times;</span><span class="sr-only">Remove</span></button></td>' +
        '<td><div class="btn-group" data-toggle="buttons"><%= radios %></div></td>' +
        '<td><span class="icon icon-<%= sphere %> fg-<%= sphere %>"></span> <span class="icon icon-<%= type %>"></span></td>' +
        '<td><a class="card card-tip" data-code="<%= code %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= name %></a></td>' +
        '</tr>');

    suggestions.div = function div(record) {
        var radios = '';
        for (var i = 0; i <= record.maxqty; i++) {
            radios += '<label class="btn btn-xs btn-default' + (i == record.indeck ? ' active' : '') + '"><input type="radio" name="qty-' + record.code + '" value="' + i + '">' + i + '</label>';
        }

        return $(suggestions.template({
            code: record.code,
            name: record.name,
            type: record.type_code,
            sphere: record.sphere_code,
            radios: radios
        }));
    };

    /**
     * @memberOf suggestions
     */
    suggestions.exclude = function exclude(code) {
        suggestions.exclusions.push(suggestions.indexFromCodes[code]);
        suggestions.compute();
    };

    /**
     * @memberOf suggestions
     */
    suggestions.pick = function pick(event) {
        var input = this;
        $(input).closest('tr').animate({
            opacity: 0
        }, 'fast', function() {
            app.ui.on_list_quantity_change.call(input, event);
        });
    };

    suggestions.setup = function() {
        suggestions.query();
        $('#table-suggestions').on({
            change: suggestions.pick
        }, 'input[type=radio]');
    };

})(app.suggestions = {}, jQuery);
