<?php

namespace AppBundle\Asset;

/**
 * The JavaScript files loaded by every page (layout.html.twig), concatenated by app:assets:js into
 * web/js/: the libraries, then the application. Paths relative to Resources/public; the order
 * matters (jQuery before its plugins, app.data.js before the modules using it).
 */
class JavascriptBundles {
    const BUNDLES = [
        'js/extra.js' => [
            'cdn/js/jquery.min.js',
            'cdn/js/jquery-ui.min.js',
            'cdn/js/underscore-min.js',
            'cdn/js/jquery.qtip.js',
            'cdn/js/typeahead.jquery.min.js',
            'cdn/js/marked.min.js',
            'cdn/js/jquery.textcomplete.min.js',
            'cdn/js/moment.min.js',
            'cdn/js/highcharts.js',
            'cdn/js/bootstrap.js',
            'cdn/js/bootstrap-markdown.min.js',
            'cdn/js/fdb-all.min.js',
        ],
        'js/app.js' => [
            'js/app.data.js',
            'js/app.format.js',
            'js/app.tip.js',
            'js/app.card_modal.js',
            'js/app.user.js',
            'js/app.binomial.js',
            'js/app.hypergeometric.js',
            'js/app.draw_simulator.js',
            'js/app.play_simulator.js',
            'js/app.textcomplete.js',
            'js/app.markdown.js',
            'js/app.smart_filter.js',
            'js/app.deck.js',
            'js/app.diff.js',
            'js/app.deck_history.js',
            'js/app.deck_charts.js',
            'js/app.deck_selection.js',
            'js/app.suggestions-mixed.js',
            'js/app.ui.js',
        ],
    ];
}
