(function(ui, $) {

    ui.create_card = function(card) {
        var content = [];

        content.push('<div class="panel panel-default selected-hero cg-' + card.sphere_code + '" title="Click to Remove">');
        content.push('  <div class="panel-heading">');
        content.push('      <h3 class="panel-title">');
        content.push('          <div class="pull-right">Threat: ' + card.threat + '</div>');
        content.push(           app.format.name(card));
        content.push('      </h3>');
        content.push('  </div>');
        content.push('<div class="panel-body card-content">');

        if (card.imagesrc) {
            content.push('<div class="card-thumbnail card-thumbnail-2x card-thumbnail-' + card.type_code + '" style="background-image:url(' + card.imagesrc + ')"></div>');
        }

        content.push('<div class="card-stats">' + app.format.stats(card) + '</div>');
        content.push('<div class="card-traits">' + app.format.traits(card) + '</div>');
        content.push('<div class="card-text">' + app.format.text(card) + '</div>');
        content.push('<span class="card-pack pull-right" style="clear:right">' + app.format.pack(card) + '</span>');
        content.push('<span class="card-sphere">' + app.format.sphere(card) + '</span>');
        content.push('</div>');

        return content.join('');
    };


    ui.initialize_hero_selection = function() {
        var scrollingDiv = $('#initHero');
        var cardImage = $('#cardimg');

        var heroesList = $('.heroes-list');
        var selectedHeroesCards = $('.selected-heroes > div');
        var selectedHeroesInputs = $('.selected-heroes input');

        var selectedHeroes = [];
        var heroCardsCache = {};

        var toggleHero = function(hero) {
            if (_.contains(selectedHeroes, hero)) {
                selectedHeroes = _.without(selectedHeroes, hero);
            } else {
                selectedHeroes.push(hero);
                if (selectedHeroes.length > 3) {
                    selectedHeroes = selectedHeroes.slice(selectedHeroes.length - 3);
                }
            }

            heroesList.find('a.active').removeClass('active');
            selectedHeroesCards.find('.selected-hero').detach();
            selectedHeroesInputs.val('');

            var totalThreat = 0;

            _.each(selectedHeroes, function(code, i) {
                var card = app.data.cards.findById(code);
                var cardCache = heroCardsCache[code];

                if (!cardCache) {
                    cardCache = heroCardsCache[code] = $(ui.create_card(card));

                    cardCache.tooltip({ placement: 'bottom' }).click(function() {
                        $(this).tooltip('hide');
                        toggleHero(code);
                    });
                }

                heroesList.find('a[data-code="' + code + '"]').addClass('active');
                selectedHeroesCards.eq(i).prepend(cardCache);
                selectedHeroesInputs.eq(i).val(code);

                totalThreat += card.threat;
            });

            $('.starting-threat span').text(totalThreat);
        };


        heroesList.on('mouseenter touchstart', 'a', function(event) {
            var code = $(this).data('code');
            var card = app.data.cards.findById(code);

            cardImage.prop('src', card.imagesrc);
        }).on('click', 'a', function(event) {
            var code = $(this).data('code');

            toggleHero(code);

            event.stopPropagation();
            event.preventDefault();
        });

        $(window).scroll(function() {
            if (!scrollingDiv.is(':visible')) {
                return;
            }

            var scrollTop = $(window).scrollTop();
            var minY = scrollingDiv.offset().top;

            var maxY = heroesList.height() - scrollingDiv.height();

            var padding = Math.min(Math.max(0,  scrollTop - minY + 20), maxY);

            scrollingDiv.css({ "paddingTop": padding });
        });
    };

    ui.on_all_loaded = function on_dom_loaded() {
        app.ui.initialize_hero_selection();
    };
})(app.ui, jQuery);
