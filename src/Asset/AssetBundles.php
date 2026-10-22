<?php

declare(strict_types=1);

namespace App\Asset;

/**
 * The files loaded by every page (layout.html.twig), built by app:assets into web/: paths relative
 * to Resources/public, in order.
 */
class AssetBundles
{
    /**
     * Concatenated: the libraries, then the application (jQuery before its plugins, app.data.js
     * before the modules using it).
     */
    public const JAVASCRIPTS = [
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

    /**
     * Concatenated, the .scss compiled: the libraries, Bootstrap, then the styles of the site (the
     * dark theme last, it overrides them).
     */
    public const STYLESHEETS = [
        'css/app.css' => [
            'cdn/css/font-awesome.min.css',
            'cdn/css/jquery.qtip.css',
            'cdn/css/bootstrap-markdown.min.css',
            'css/bootstrap.css',
            'css/style.scss',
            'css/icons.scss',
            'css/dark.scss',
        ],
    ];
}
