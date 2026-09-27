<?php

namespace AppBundle\Services;

class Texts {
    /**
     * @var \HTMLPurifier
     */
    private $purifier_service;

    /**
     * @var \Parsedown
     */
    private $markdown_service;

    /**
     * @param string $cache_dir where HTMLPurifier caches its definitions
     */
    public function __construct($cache_dir) {
        // HTMLPurifier does not create its base cache directory, and warns if it is missing
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0775, true);
        }
        $config = \HTMLPurifier_Config::create(['Cache.SerializerPath' => $cache_dir]);
        $def = $config->getHTMLDefinition(true);
        $def->addAttribute('a', 'data-code', 'Text');
        $this->purifier_service = new \HTMLPurifier($config);

        $this->markdown_service = new \Parsedown();
    }

    /**
     * Returns the processed version of a markdown text
     * @param mixed $string
     * @return string
     */
    public function markdown($string) {
        return $this->purify($this->img_responsive($this->transform($string)));
    }

    /**
     * removes any dangerous code from a HTML string
     *
     * @param mixed $string
     * @return string
     */
    public function purify($string) {
        return $this->purifier_service->purify($string);
    }

    /**
     * turns a Markdown string into a HTML string
     *
     * @param mixed $string
     * @return string
     */
    public function transform($string) {
        return $this->markdown_service->text($string);
    }

    /**
     * adds class="img-responsive" to every <img> tag
     *
     * @param mixed $string
     * @return string
     */
    public function img_responsive($string) {
        return preg_replace('/<img/', '<img class="img-responsive"', $string);
    }

    /**
     * Transforms the string into a valid filename, lower-case, no spaces, pure ASCII, etc.
     *
     * @param string $filename
     * @return string
     */
    public function slugify($filename) {
        $filename = preg_replace('[^\w\-]', '-', $filename);
        // //TRANSLIT is not supported by every iconv implementation (e.g. musl on Alpine)
        $ascii = @iconv('utf-8', 'us-ascii//TRANSLIT', $filename);
        $filename = $ascii !== false ? $ascii : preg_replace('/[^\x00-\x7F]/', '', $filename);
        $filename = preg_replace('/[^\w\-]/', '', $filename);
        $filename = preg_replace('/\-+/', '-', $filename);
        $filename = trim($filename, '-');
        $filename = strtolower($filename);

        return $filename;
    }
}
