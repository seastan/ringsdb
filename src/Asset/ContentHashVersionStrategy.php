<?php

declare(strict_types=1);

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
     * @var array<string, string>
     */
    private array $versions = [];

    public function __construct(private readonly string $publicDir)
    {
    }

    /**
     * @param string $path
     */
    public function getVersion($path): string
    {
        if (!isset($this->versions[$path])) {
            $file = $this->publicDir.'/'.ltrim($path, '/');
            $hash = preg_match('/\.(js|css)$/', $path) && is_file($file) ? md5_file($file) : false;
            $this->versions[$path] = false !== $hash ? substr($hash, 0, 8) : '';
        }

        return $this->versions[$path];
    }

    /**
     * @param string $path
     */
    public function applyVersion($path): string
    {
        $version = $this->getVersion($path);

        return '' === $version ? $path : $path.'?v='.$version;
    }
}
