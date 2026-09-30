(function app_draw_simulator(draw_simulator, $) {

    var deck = null;
    var hand = [];
    var initial_size = 0;
    var draw_count = 0;
    var container = null;

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.render = function () {
        $(container).empty();
        $('[data-command=clear],[data-command=reshuffle],[data-command=discard]').prop('disabled', true);
        hand.forEach(function (card, i) {
            $('[data-command=clear]').prop('disabled', false);
            var card_element;
            if (card.data.imagesrc) {
                card_element = $('<img src="' + card.data.imagesrc + '">');
            } else {
                card_element = $('<div class="card-proxy"><div>' + card.data.name + '</div></div>');
            }
            card_element.attr('data-hand-id', i);
            if (card.selected) {
                card_element.css('opacity', 0.6);
                $('[data-command=reshuffle],[data-command=discard]').prop('disabled', false);
            }
            container.append(card_element);
        });
        draw_simulator.update_odds();
    }
    /**
     * @memberOf draw_simulator
     */
    draw_simulator.reset = function reset() {
        $(container).empty();
        draw_simulator.on_data_loaded();
        draw_count = 0;
        hand = [];
        draw_simulator.render();
    };

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.discard = function (reshuffle) {
        for (var i = hand.length - 1; i >= 0; i--) {
            var card = hand[i];
            if (card.selected) {
                card.selected = false;
                hand.splice(i, 1);
                if (reshuffle) {
                    deck.push(card);
                }
                draw_count--;
            }
        }

        draw_simulator.render();
    };

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.on_dom_loaded = function() {
        $('#table-draw-simulator')
            .on('click', 'button.btn', draw_simulator.handle_click)
            .on('click', 'img, div.card-proxy', draw_simulator.select_card);
        container = $('#table-draw-simulator-content');


        $('#oddsModal').on('input', 'input', draw_simulator.compute_odds);
        draw_simulator.compute_odds();
    };

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.select_card = function select_card(event) {
        var index = $(this).attr('data-hand-id');
        if (hand[index]) {
            hand[index].selected = !hand[index].selected;
        }
        draw_simulator.render();
    }

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.compute_odds = function() {
        var inputs = {};
        $.each(['N', 'K', 'n', 'k'], function(i, key) {
            inputs[key] = parseInt($('#odds-calculator-' + key).val(), 10) || 0;
        });
        $('#odds-calculator-p').text(Math.round(100 * app.hypergeometric.get_cumul(inputs.k, inputs.N, inputs.K, inputs.n)));
    };

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.on_data_loaded = function on_data_loaded() {
        deck = [];

        var cards = app.deck.get_draw_deck();
        cards.forEach(function(card) {
            for (var ex = 0; ex < card.indeck; ex++) {
                deck.push({ data: card });
            }
        });
        initial_size = deck.length;
    };

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.update_odds = function() {
        for (var i = 1; i <= 3; i++) {
            var odd = app.hypergeometric.get_cumul(1, initial_size, i, draw_count);
            $('#draw-simulator-odds-' + i).text(Math.round(100 * odd));
        }
    };

    /**
     * @memberOf draw_simulator
     * @param draw integer
     */
    draw_simulator.do_draw = function do_draw(draw) {
        for (var pick = 0; pick < draw && deck.length > 0; pick++) {
            var rand = Math.floor(Math.random() * deck.length);
            var spliced = deck.splice(rand, 1);
            var card = spliced[0];
            hand.push(card);
            draw_count++;
        }
        draw_simulator.render();
    };

    /**
     * @memberOf draw_simulator
     */
    draw_simulator.handle_click = function handle_click(event) {
        event.preventDefault();

        var command = $(this).data('command');
        if (command === 'reshuffle') {
            draw_simulator.discard(true);
            return;
        } else if (command === 'discard') {
            draw_simulator.discard(false);
            return;
        } else if (command === 'clear') {
            draw_simulator.reset();
            return;
        }

        if (event.shiftKey) {
            draw_simulator.reset();
        }

        var draw;
        if (command === 'all') {
            draw = deck.length;
        } else {
            draw = command;
        }

        if (isNaN(draw)) return;
        draw_simulator.do_draw(draw);
    };
})(app.draw_simulator = {}, jQuery);
