<?php

namespace Formwork\Assets;

use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;

class Assets
{
    /**
     * Base path where asset files are located
     */
    protected string $basePath;

    /**
     * Base URI from which assets are accessible
     */
    protected string $baseUri;

    /**
     * Create a new Assets instance
     */
    public function __construct(string $basePath, string $baseUri)
    {
        $this->basePath = FileSystem::normalizePath($basePath);
        $this->baseUri = Uri::normalize($baseUri);
    }

    /**
     * Get asset version, if possible, based on its last modified time
     *
     * @param string $path Requested asset path
     */
    public function version(string $path): ?string
    {
        $file = FileSystem::joinPaths($this->basePath, $path);
        if (FileSystem::exists($file)) {
            return dechex(FileSystem::lastModifiedTime($file));
        }
        return null;
    }

    /**
     * Get a SHA-256 integrity hash for an asset if possible
     */
    public function integrityHash(string $path): ?string
    {
        $file = FileSystem::joinPaths($this->basePath, $path);
        if (FileSystem::exists($file)) {
            return 'sha256-' . base64_encode(hash('sha256', FileSystem::read($file), true));
        }
        return null;
    }

    /**
     * Get asset URI optionally followed by a version query parameter
     *
     * @param string $path           Requested asset path
     * @param bool   $includeVersion Whether to include asset version
     */
    public function uri(string $path, bool $includeVersion = false): string
    {
        $uri = $this->baseUri . trim($path, '/');
        if ($includeVersion && ($version = $this->version($path)) !== null) {
            $uri .= '?v=' . $version;
        }
        return $uri;
    }
}
