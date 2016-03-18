(function app_deck_charts(deck_charts, $) {

    var sphere_colors = {
        spirit: '#00B1D4',
        lore: '#51B848',
        tactics: '#ED2E30',
        leadership: '#AD62A5',
        neutral: '#616161',
        baggins: '#B39E26',
        fellowship: '#B56C0C'
    };

    deck_charts.chart_sphere = function chart_sphere() {
        var spheres = {};
        var draw_deck = app.deck.get_draw_deck();

        draw_deck.forEach(function(card) {
            if (!spheres[card.sphere_code]) {
                spheres[card.sphere_code] = {
                    code: card.sphere_code,
                    name: card.sphere_name,
                    count: 0
                };
            }

            spheres[card.sphere_code].count += card.indeck;
        });

        var data = [];
        _.each(_.values(spheres), function(sphere) {
            data.push({
                name: sphere.name,
                label: '<span class="icon icon-' + sphere.code + '"></span>',
                color: sphere_colors[sphere.code],
                y: sphere.count
            });
        });

        $("#deck-chart-sphere").highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: "Card Spheres"
            },
            subtitle: {
                text: "Draw deck only"
            },
            xAxis: {
                categories: _.pluck(data, 'label'),
                labels: {
                    useHTML: true
                },
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                tickInterval: 3,
                title: null,
                labels: {
                    overflow: 'justify'
                }
            },
            series: [{
                type: "column",
                animation: false,
                name: '# cards',
                showInLegend: false,
                data: data
            }],
            plotOptions: {
                column: {
                    borderWidth: 0,
                    groupPadding: 0,
                    shadow: false
                }
            }
        });
    };

    deck_charts.chart_type = function chart_type() {

        var data = [{
            name: 'Ally',
            label: '<span class="icon icon-ally"></span>',
            color: '#ea7910',
            y: 0
        }, {
            name: 'Attachment',
            label: '<span class="icon icon-attachment"></span>',
            color: '#13522f',
            y: 0
        }, {
            name: 'Event',
            label: '<span class="icon icon-event"></span>',
            color: '#292e5f',
            y: 0
        }, {
            name: 'Player Side Quest',
            label: '<span class="icon icon-player-side-quest"></span>',
            color: '#c8232a',
            y: 0
        }];

        var iData = {
            'ally': data[0],
            'attachment': data[1],
            'event': data[2],
            'player-side-quest': data[3]
        };

        var draw_deck = app.deck.get_draw_deck();
        draw_deck.forEach(function(card) {
            var d = iData[card.type_code];
            if (d) {
                d.y += card.indeck;
            }
        });

        $("#deck-chart-type").highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: "Card Types"
            },
            subtitle: {
                text: ""
            },
            xAxis: {
                categories: _.pluck(data, 'label'),
                labels: {
                    useHTML: true
                },
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                tickInterval: 2,
                title: null,
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size: 10px">{point.key}</span><br/>'
            },
            series: [{
                type: "column",
                animation: false,
                name: '# cards',
                showInLegend: false,
                data: data
            }],
            plotOptions: {
                column: {
                    borderWidth: 0,
                    groupPadding: 0,
                    shadow: false
                }
            }
        });
    };

    deck_charts.chart_stats = function chart_stats() {

        var data = [{
            name: 'Willpower',
            label: '<span class="icon icon-willpower"></span>',
            color: '#ea7910',
            y: 0
        }, {
            name: 'Attack',
            label: '<span class="icon icon-attack"></span>',
            color: '#13522f',
            y: 0
        }, {
            name: 'Defense',
            label: '<span class="icon icon-defense"></span>',
            color: '#292e5f',
            y: 0
        }, {
            name: 'Hit Points',
            label: '<span class="icon icon-health"></span>',
            color: '#c8232a',
            y: 0
        }];

        var draw_deck = app.deck.get_draw_deck();
        draw_deck.forEach(function(card) {
            if (card.type_code == 'hero' || card.type_code == 'ally') {
                if (typeof card.willpower === 'number') {
                    data[0].y += card.willpower * card.indeck;
                }
                if (typeof card.attack === 'number') {
                    data[1].y += card.attack * card.indeck;
                }
                if (typeof card.defense === 'number') {
                    data[2].y += card.defense * card.indeck;
                }
                if (typeof card.health === 'number') {
                    data[3].y += card.health * card.indeck;
                }
            }
        });

        $("#deck-chart-stats").highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: "Card Stats"
            },
            subtitle: {
                text: "Stats added for Heroes and Allies"
            },
            xAxis: {
                categories: _.pluck(data, 'label'),
                labels: {
                    useHTML: true
                },
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                tickInterval: 2,
                title: null,
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size: 10px">{point.key}</span><br/>'
            },
            series: [{
                type: "column",
                animation: false,
                name: '# cards',
                showInLegend: false,
                data: data
            }],
            plotOptions: {
                column: {
                    borderWidth: 0,
                    groupPadding: 0,
                    shadow: false
                }
            }
        });
    };

    deck_charts.chart_cost = function chart_cost() {
        var data = [];

        var draw_deck = app.deck.get_draw_deck();
        draw_deck.forEach(function(card) {
            var cost = parseInt(card.cost, 10);
            if (!isNaN(cost)) {
                data[cost] = data[cost] || 0;
                data[cost] += card.indeck;
            }
        });

        data = _.flatten(data).map(function(value) {
            return value || 0;
        });

        $("#deck-chart-cost").highcharts({
            chart: {
                type: 'line'
            },
            title: {
                text: "Card Cost"
            },
            subtitle: {
                text: "Cost X ignored"
            },
            xAxis: {
                allowDecimals: false,
                tickInterval: 1,
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                tickInterval: 1,
                title: null,
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size: 10px">Cost {point.key}</span><br/>'
            },
            series: [{
                animation: false,
                name: '# cards',
                showInLegend: false,
                data: data
            }]
        });
    };

    deck_charts.setup = function setup() {
        deck_charts.chart_sphere();
        deck_charts.chart_type();
        deck_charts.chart_stats();
        deck_charts.chart_cost();
    };

    $(document).on('shown.bs.tab', 'a[data-toggle=tab]', function(e) {
        deck_charts.setup();
    });

})(app.deck_charts = {}, jQuery);
