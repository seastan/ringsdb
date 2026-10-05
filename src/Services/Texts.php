<?php

declare(strict_types=1);

namespace App\Services;

use HTMLPurifier;

class Texts
{
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
    public function __construct($cache_dir)
    {
        // HTMLPurifier does not create its base cache directory, and warns if it is missing
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0775, true);
        }

        $config = \HTMLPurifier_Config::create(['Cache.SerializerPath' => $cache_dir]);
        // raw definition: never null
        /** @var \HTMLPurifier_HTMLDefinition $def */
        $def = $config->getHTMLDefinition(true);
        $def->addAttribute('a', 'data-code', 'Text');

        $this->purifier_service = new \HTMLPurifier($config);

        $this->markdown_service = new \Parsedown();
    }

    /**
     * Returns the processed version of a markdown text.
     */
    public function markdown(string $string): string
    {
        return $this->purify($this->img_responsive($this->transform($string)));
    }

    /**
     * removes any dangerous code from a HTML string.
     */
    public function purify(string $string): string
    {
        return $this->purifier_service->purify($string);
    }

    /**
     * turns a Markdown string into a HTML string.
     */
    public function transform(string $string): string
    {
        return $this->markdown_service->text($string);
    }

    /**
     * adds class="img-responsive" to every <img> tag.
     */
    public function img_responsive(string $string): string
    {
        $replace = preg_replace('/<img/', '<img class="img-responsive"', $string);
        if (null === $replace) {
            throw new \RuntimeException('Unable to parse image string '.$string);
        }

        return $replace;
    }

    /**
     * Transforms the string into a valid filename, lower-case, no spaces, pure ASCII, etc.
     */
    public function slugify(string $filename): string
    {
        $filename = (string) preg_replace('[^\w\-]', '-', $filename);
        // //TRANSLIT is not supported by every iconv implementation (e.g. musl on Alpine)
        $ascii = @iconv('utf-8', 'us-ascii//TRANSLIT', $filename);
        $filename = false !== $ascii ? $ascii : (string) preg_replace('/[^\x00-\x7F]/', '', $filename);
        $filename = (string) preg_replace('/[^\w\-]/', '', $filename);
        $filename = (string) preg_replace('/\-+/', '-', $filename);
        $filename = trim($filename, '-');

        return strtolower($filename);
    }
}
