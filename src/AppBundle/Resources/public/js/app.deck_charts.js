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

    function convertHex(hex, opacity) {
        hex = hex.replace('#','');
        var r = parseInt(hex.substring(0,2), 16);
        var g = parseInt(hex.substring(2,4), 16);
        var b = parseInt(hex.substring(4,6), 16);

        return 'rgba(' + r +', ' + g + ', ' + b +', ' + opacity +')';
    };

    function darken(hex, pct) {
        hex = hex.replace('#','');
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);

        return '#' + pad(Math.floor(r*pct).toString(16), '00') + pad(Math.floor(g*pct).toString(16), '00') + pad(Math.floor(b*pct).toString(16), '00');
    };

    function pad(value, format) {
        return format.substring(0, format.length - value.length) + value;
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

        var categories = {
            'Ally': '<span class="icon icon-ally"></span>',
            'Attachment': '<span class="icon icon-attachment"></span>',
            'Event': '<span class="icon icon-event"></span>',
            'Player Side Quest': '<span class="icon icon-player-side-quest"></span>',
            'Contract': '<span class="icon icon-contract"></span>',
            'Treasure': '<span class="icon icon-treasure"></span>'
        };

        var iData = {
            'ally': { i: 0, name: 'Ally' },
            'attachment': { i: 1, name: 'Attachment' },
            'event': { i: 2, name: 'Event' },
            'player-side-quest': { i: 3, name: 'Player Side Quest' },
            'contract': { i: 4, name: 'Contract' },
            'treasure': { i: 5, name: 'Treasure' }
        };

        var validTypes = {};
        var validIndexes = {};

        var series = [];
        var iSeries = {};

        var draw_deck = app.deck.get_draw_deck();
        draw_deck.forEach(function(card) {
            var serie;

            if (!iSeries[card.sphere_code]) {
                serie = {
                    name: card.sphere_name,
                    color: sphere_colors[card.sphere_code],
                    data: [0, 0, 0, 0, 0],
                    type: "column",
                    animation: false,
                    showInLegend: false
                };
                iSeries[card.sphere_code] = serie;
                series.push(serie);
            } else {
                serie = iSeries[card.sphere_code];
            }

            var d = iData[card.type_code];
            if (d !== undefined) {
                validTypes[d.name] = true;
                validIndexes[d.i] = true;
                serie.data[d.i] += card.indeck;
            }
        });

        categories = _.omit(categories, function(value, key) {
            return !validTypes[key];
        });

        _.each(series, function(serie) {
            serie.data = _.filter(serie.data, function(value, index) {
                return validIndexes[index];
            });
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
                type: 'category',
                categories: _.keys(categories),
                labels: {
                    useHTML: true,
                    formatter: function() {
                        return categories[this.value];
                    }
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
                headerFormat: '<b>{point.x}</b><br/>',
                pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
            },
            //tooltip: {
            //    headerFormat: '<span style="font-size: 10px">{point.key}</span><br/>'
            //},
            series: series,
            plotOptions: {
                column: {
                    stacking: 'normal',
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

        var cards = app.deck.get_cards();
        cards.forEach(function(card) {
            if (card.type_code == 'hero' || card.type_code == 'ally') {
                var count = card.is_unique ? 1 : card.indeck;

                if (typeof card.willpower === 'number') {
                    data[0].y += card.willpower * count;
                }
                if (typeof card.attack === 'number') {
                    data[1].y += card.attack * count;
                }
                if (typeof card.defense === 'number') {
                    data[2].y += card.defense * count;
                }
                if (typeof card.health === 'number') {
                    data[3].y += card.health * count;
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
                text: "Stats added for Heroes and Allies<br>Unique allies counted only once"
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

    deck_charts.chart_cost_sphere = function() {
        var spheres = {};
        var cards = app.deck.get_draw_deck();

        cards.forEach(function(card) {
            var cost = parseInt(card.cost, 10);
            if (isNaN(cost)) {
                return;
            }

            var count = card.is_unique ? 1 : card.indeck;
            var ucount = card.is_unique ? Math.max(0, card.indeck - 1) : 0;

            if (!spheres[card.sphere_code]) {
                spheres[card.sphere_code] = {
                    code: card.sphere_code,
                    name: card.sphere_name,
                    count: [0, 0]
                };
            }

            spheres[card.sphere_code].count[0] += count * card.cost;
            spheres[card.sphere_code].count[1] += ucount * card.cost;
        });

        var dataUnique = [];
        _.each(_.values(spheres), function(sphere) {
            dataUnique.push({
                name: sphere.name,
                label: '<span class="icon icon-' + sphere.code + '"></span>',
                color: sphere_colors[sphere.code],
                y: sphere.count[0]
            });
        });

        var data = [];
        _.each(_.values(spheres), function(sphere) {
            data.push({
                name: sphere.name,
                label: '<span class="icon icon-' + sphere.code + '"></span>',
                color: darken(sphere_colors[sphere.code], 0.6),
                y: sphere.count[1]
            });
        });

        $("#deck-chart-cost-sphere").highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: "Total Cost by Sphere"
            },
            subtitle: {
                text: "Cost X ignored"
            },
            xAxis: {
                categories: _.pluck(data, 'name'),
                labels: {
                    useHTML: true,
                    formatter: function() {
                        return _.find(data, { name: this.value }).label;
                    }
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
                },
                stackLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold',
                        color: 'gray'
                    }
                }
            },
            tooltip: {
                headerFormat: '<b>{point.x}</b><br/>',
                pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
            },
            series: [{
                type: "column",
                animation: false,
                name: 'Cost of duplicate uniques',
                showInLegend: false,
                data: data
            }, {
                type: "column",
                animation: false,
                name: 'Cost counting uniques once',
                showInLegend: false,
                data: dataUnique
            }],
            plotOptions: {
                column: {
                    stacking: 'normal',
                    borderWidth: 0,
                    groupPadding: 0,
                    shadow: false
                }
            }
        });
    };

    deck_charts.setup = function setup() {
        deck_charts.chart_sphere();
        deck_charts.chart_type();
        deck_charts.chart_stats();
        deck_charts.chart_cost();
        deck_charts.chart_cost_sphere();
    };

    $(document).on('shown.bs.tab', 'a[data-toggle=tab]', function(e) {
        deck_charts.setup();
    });

})(app.deck_charts = {}, jQuery);
