(function app_play_simulator(play_simulator, $) {

    var global_buttons_loaded = false;

    play_simulator.reset = function reset() {
        play_simulator.setup_deck();
        play_simulator.define_global_buttons(); 
    };

    play_simulator.is_patron = function() {
        if (app.user.data && app.user.data.donation > 0) return true;
        else return false;
    };

    play_simulator.get_card_html = function get_card_html(card) {
        var card_html = '<li class="list-group-item card-type-' + card.type_code + '">' +
                        card_html_a + 
                        '<table class="tab-card-border tab-card-' + card.sphere_code + '" style="width:100%">' + 
                        card_html_b + 
                        '<a href="' + Routing.generate('cards_zoom', { card_code: card.code }) + '" class="card-tip card-title" data-code="' + card.code + '">' + card.name + '</a>' + 
                        card_html_c;
        return card_html;
    };

    play_simulator.draw_card = function draw_card() {
        var html_card = $('#list-indeck').children().get(0);
        $(html_card).prependTo('#list-inhand');
        $(html_card).makeFaceup();
    };

    play_simulator.on_dom_loaded = function() {

        if (play_simulator.is_patron()) {
            $('#span-patreon-simu').css('display','none');
            $('#overlay-simu').css('display','none');
            $('#base-simu').removeClass('base-simu-unsupported');
        }
   
    };

    play_simulator.setup_deck = function setup_deck() {
        // Empty all lists
        $('.list-group').empty();

        // Set up heroes
        var cards = app.deck.get_hero_deck();
        cards.forEach(function(card) {
            $('#list-inplay').append(play_simulator.get_card_html(card));
        });
        $('#list-inplay').find('.tab-tokens').css('display', 'inline');
        // Set starting threat
        $('#div-threat').find('.lab-value').text(app.deck.get_starting_threat());
        // Set rounds to 0
        $('#div-rounds').find('.lab-value').text('0');

        // Set up deck
        var heroes = app.deck.get_draw_deck();
        heroes.forEach(function(card) {
            for (var ex = 0; ex < card.indeck; ex++) {
                $('#list-indeck').append(play_simulator.get_card_html(card));
            }
        });
        // Make cards in deck face down
        $('#list-indeck').find('.list-group-item').makeFacedown();

        // Shuffle deck
        $('#list-indeck').randomize();

        //Draw starting hand
        for (var i = 0; i < 6; i++) play_simulator.draw_card();

        // Program card buttons
        play_simulator.define_card_buttons();
    };

    play_simulator.define_global_buttons = function define_global_buttons() {
        // Only do this once, not every time the game is reset

        if (!play_simulator.is_patron() || global_buttons_loaded) return; 
        global_buttons_loaded = true;

        $('#btn-draw').click(function() {
            play_simulator.draw_card();
        });

        $('#btn-shuffle-deck').click(function() {
            $('#list-indeck').randomize();
        });

        $('#sel-reveal').change(function() {
            var list_indeck = $('#list-indeck').find('.list-group-item');
            var num_to_reveal = parseInt(this.value);
            list_indeck.each(function(i, html_card) {
                var card = $(html_card);
                if (i<num_to_reveal) card.makeFaceup();
                else card.makeFacedown();
            });
            this.value = "";
        });

        $('#sel-hand-other').change(function() {
            var list_inhand = $('#list-inhand').find('.list-group-item');
            var hand_size = list_inhand.length;
            var option = this.value;
            if (option == 'discardRandom') {
                var r = Math.floor(Math.random() * hand_size);
                var card = $(list_inhand.get(r));
                card.discard();
            }
            if (option == 'shuffleRedraw') {
                list_inhand.each(function(i, html_card) {
                    var card = $(html_card);
                    card.shuffleIntoDeck();
                });
                $('#list-indeck').randomize('li');
                for (var i = 0; i < hand_size; i++) play_simulator.draw_card();
            }
            this.value = "";
        });

        $('#btn-refresh').click(function() {
            $('.list-group-item').readyCard();
            var labelThreat = $('#div-threat').find('.lab-value');
            labelThreat.text(parseInt(labelThreat.text()) + 1);
        });

        $('#btn-new-round').click(function() {
            var labelRounds = $('#div-rounds').find('.lab-value');
            labelRounds.text(parseInt(labelRounds.text()) + 1);
            list_labelHeroRes = $('#list-inplay').find('.card-type-hero').find('.container-resources').find('.lab-value');
            list_labelHeroRes.each(function(i,html_labelHeroRes) {
                labelHeroRes = $(html_labelHeroRes);
                labelHeroRes.text(parseInt(labelHeroRes.text()) + 1);
            });
            play_simulator.draw_card();
        });

        $('#btn-reset-game').click(function() {
            play_simulator.reset();
        });

        $('.btn-header-value-up').click(function() {
            var card = $(this).parents('.container-value');
            var tok = card.find('.lab-value');
            var newval = parseInt(tok.text()) + 1;
            if (newval < 0) newval = 0;
            tok.text(newval);
        });
        
        $('.btn-header-value-dn').click(function() {
            var card = $(this).parents('.container-value');
            var tok = card.find('.lab-value');
            var newval = parseInt(tok.text()) - 1;
            if (newval < 0) newval = 0;
            tok.text(newval);
        });
        
        $(".list-group").sortable();

    };

    play_simulator.define_card_buttons = function define_card_buttons() {
        // We do this every time the game is reset and new cards are created

        $('.btn-play').click(function() {
            var card = $(this).parents('.list-group-item');
            card.appendTo('#list-inplay');
            card.makeFaceup();
            card.enterPlay();
        });

        $('.btn-disc').click(function() {
            var card = $(this).parents('.list-group-item');
            card.discard();
        });

        $('.sel-moveto').change(function() {
            var card = $(this).parents('.list-group-item');
            card.leavePlay();
            if (this.value == "hand") {
                card.appendTo('#list-inhand');
                card.makeFaceup();
            } else if (this.value == "top") {
                card.makeFacedown();
                card.prependTo('#list-indeck');
            } else if (this.value == "middle") {
                card.shuffleIntoDeck();
            } else if (this.value == "bottom") {
                card.makeFacedown();
                card.appendTo('#list-indeck');
            }
            this.value = "";
        });

        $('.btn-move-up').click(function() {
            var card = $(this).parents('.list-group-item');
            card.moveUp();
        });

        $('.btn-move-dn').click(function() {
            var card = $(this).parents('.list-group-item');
            card.moveDown();
        });


        $('.btn-exhaust').click(function() {
            var card = $(this).parents('.list-group-item');
            card.exhaustCard();
        });
        
        $('.btn-ready').click(function() {
            var card = $(this).parents('.list-group-item');
            card.readyCard();
        });

        $('.btn-value-up').click(function() {
            var card = $(this).parents('.container-value');
            var tok = card.find('.lab-value');
            var newval = parseInt(tok.text()) + 1;
            if (newval < 0) newval = 0;
            tok.text(newval);
        });
        
        $('.btn-value-dn').click(function() {
            var card = $(this).parents('.container-value');
            var tok = card.find('.lab-value');
            var newval = parseInt(tok.text()) - 1;
            if (newval < 0) newval = 0;
            tok.text(newval);
        });


        $('.btn-show').click(function() {
            $(this).parents('.td-list').find('.list-group').removeClass('list-group-hidden');
            $(this).css('display','none');
            $(this).parents('.td-list').find('.btn-hide').css('display','inline');
        });

        $('.btn-hide').click(function() {
            $(this).parents('.td-list').find('.list-group').addClass('list-group-hidden');
            $(this).css('display','none');
            $(this).parents('.td-list').find('.btn-show').css('display','inline');
        });

        
    };

    play_simulator.on_data_loaded = function on_data_loaded() {

        play_simulator.define_global_buttons();

    };

    var card_html_a = '<table style="width:100%">' +
    '<tr>' +
    '<td>'
    var card_html_b = '<tr>' +
        '<td>' +
        '<table class="tab-tokens" style="display:none">' +
            '<tr>' +
            '<td>' +
            '<div class="container-value container-resources">' +
                '<div class="btn-lab btn-lab-up fa fa-angle-up"></div>' +
                '<div class="btn-lab btn-lab-dn fa fa-angle-down"></div>' +
                '<div class="btn-value btn-value-up"></div>' +
                '<div class="btn-value btn-value-dn"></div>' +
            '<div class="lab-value">0</div>' +
            '</div>' +
            '</td>' +
            '<td>' +
            '<div class="container-value container-damage">' +
                '<div class="btn-lab btn-lab-up fa fa-angle-up"></div>' +
                '<div class="btn-lab btn-lab-dn fa fa-angle-down"></div>' +
                '<div class="btn-value btn-value-up"></div>' +
                '<div class="btn-value btn-value-dn"></div>' +
            '<div class="lab-value">0</div>' +
            '</div>' +
            '</td>' +
            '</tr>' +
        '</table>' +
        '</td>' +
        '<td>'
    // Card data  
    var card_html_c = '</td>' +
        '<td align="right">' +
        '<table class="tab-card-buttons">' +
            '<tr>' +
            '<td><button class="btn-simu btn-play">Play</button></td>' +
            '<td>' +
                '<button class="btn-simu btn-exhaust">Exhaust</button>' +
                '<button class="btn-simu btn-ready" style="display:none;">Ready</button>' +
            '</td>' +
            '<td><button class="btn-simu btn-move-up fa fa-angle-up" style="width:40px;"></button></td>' +
            '<td rowspan="2"><div class="fa fa-drag-handle"</div></td>' +
            '</tr>' +
            '<tr>' +
            '<td><button class="btn-simu btn-disc">Discard</button></td>' +
            '<td>' +
            '<select class="btn-simu sel-simu sel-moveto">' +
            '<option value="" selected disabled hidden>Move to...</option>' +
            '<option value="hand">Hand</option>' +
            '<option value="top">Top of deck</option>' +
            '<option value="middle">Shuffle into deck</option>' +
            '<option value="bottom">Bottom of deck</option>' +
            '</select>' +
            '</td>' +
            '<td><button class="btn-simu btn-move-dn fa fa-angle-down" style="width:40px;"></button></td>' +
            '</tr>' +
        '</table>' +
        '</td>' +
        '</tr>' +
        '</table>' +
    '</td>' +
    '</tr>' +
    '</table>' +
    '</li>'

    $.fn.discard = function() {
        $(this).leavePlay();
        $(this).prependTo('#list-indisc');
        $(this).makeFaceup();
    }    
    
    $.fn.shuffleIntoDeck = function() {
        $(this).makeFacedown();
        $(this).prependTo('#list-indeck');
        $('#list-indeck').randomize('li');
    }

    $.fn.moveUp = function() {
        before = $(this).prev();
        $(this).insertBefore(before);
    }
    
    $.fn.moveDown = function() {
        after = $(this).next();
        $(this).insertAfter(after);
    }
    
    $.fn.enterPlay = function() {
        $(this).find('.tab-tokens').css('display', 'inline');
        $(this).readyCard();
        // Format all attachments
        $('#list-inplay').find('.card-type-attachment').addClass('indent-attachment');
        $('#list-inplay').find('.card-type-attachment').find('.tab-tokens').css('display', 'none');
    }

    $.fn.leavePlay = function() {
        $(this).find('.tab-tokens').css('display', 'none');
        $(this).zeroTokens();
        $(this).readyCard();
        $(this).removeClass('indent-attachment');
    }
    
    $.fn.zeroTokens = function() {
        var tok = $(this).find('.lab-value');
        tok.text(0);
    }
    
    $.fn.randomize = function() {
        var ul = this.get(0);
        for (var i = ul.children.length; i >= 0; i--) {
            var r = Math.random();
            var card = ul.children[Math.random() * i | 0];
            if (card) ul.appendChild(card);
        }
    };

    $.fn.makeFacedown = function() {
        $(this).addClass('list-group-item-facedown');
        $(this).find('.tab-card-border').addClass('tab-card-border-facedown');
        $(this).find('.card-tip').addClass('card-tip-facedown');
    };
    $.fn.makeFaceup = function() {
        $(this).removeClass('list-group-item-facedown');
        $(this).find('.tab-card-border').removeClass('tab-card-border-facedown');
        $(this).find('.card-tip').removeClass('card-tip-facedown');
    };

    $.fn.exhaustCard = function() {
        $(this).find('.btn-exhaust').css('display','none');
        $(this).find('.btn-ready').css('display','inline');
        $(this).find('.tab-card-border').addClass('tab-card-border-exhausted');
    };

    $.fn.readyCard = function() {
        $(this).find('.btn-exhaust').css('display','inline');
        $(this).find('.btn-ready').css('display','none');
        $(this).find('.tab-card-border').removeClass('tab-card-border-exhausted');
    };

})(app.play_simulator = {}, jQuery);
