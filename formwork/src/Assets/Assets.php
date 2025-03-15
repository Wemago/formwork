<?php

namespace Formwork\Assets;

use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
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
     * Asset collection
     */
    private AssetCollection $collection;

    public function __construct(string $basePath, string $baseUri)
    {
        $this->basePath = FileSystem::normalizePath($basePath);
        $this->baseUri = Uri::normalize(Str::append($baseUri, '/'));
        $this->collection = new AssetCollection();
    }

    /**
     * Add an asset to the collection
     */
    public function add(string $key): void
    {
        if (!$this->collection->has($key)) {
            $path = FileSystem::joinPaths($this->basePath, Path::resolve($key, '/', DIRECTORY_SEPARATOR));
            $uri = Path::join([$this->baseUri, Path::resolve($key, '/')]);
            $this->collection->set($key, new Asset($path, $uri));
        }
    }

    /**
     * Get an asset from the collection
     */
    public function get(string $key): Asset
    {
        if (!$this->collection->has($key)) {
            $path = FileSystem::joinPaths($this->basePath, Path::resolve($key, '/', DIRECTORY_SEPARATOR));
            $uri = Path::join([$this->baseUri, Path::resolve($key, '/')]);
            $this->collection->set($key, new Asset($path, $uri));
        }
        return $this->collection->get($key);
    }

    /**
     * Get stylesheets from the assets collection
     */
    public function stylesheets(): AssetCollection
    {
        return $this->collection->stylesheets();
    }

    /**
     * Get scripts from the assets collection
     */
    public function scripts(): AssetCollection
    {
        return $this->collection->scripts();
    }

    /**
     * Get images from the assets collection
     */
    public function images(): AssetCollection
    {
        return $this->collection->images();
    }
}
