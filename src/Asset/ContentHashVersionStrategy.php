<?php

namespace App\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Versions the URLs of the JavaScript and CSS files with a hash of their content (?v=...), so that
 * the browsers load them again when they change (Assetic's cache busting did it before). The other
 * assets (images...), and the files missing from public/, are left unversioned.
 */
class ContentHashVersionStrategy implements VersionStrategyInterface
{
    /**
     * @var string
     */
    private $webDir;

    /**
     * @var array<string, string>
     */
    private $versions = [];

    public function __construct(string $publicDir)
    {
        $this->webDir = $publicDir;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getVersion($path)
    {
        if (!isset($this->versions[$path])) {
            $file = $this->webDir.'/'.ltrim($path, '/');
            $hash = preg_match('/\.(js|css)$/', $path) && is_file($file) ? md5_file($file) : false;
            $this->versions[$path] = false !== $hash ? substr($hash, 0, 8) : '';
        }

        return $this->versions[$path];
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function applyVersion($path)
    {
        $version = $this->getVersion($path);

        return '' === $version ? $path : $path.'?v='.$version;
    }
}
