<?php

namespace Formwork\Plugins;

use Composer\Autoload\ClassLoader;
use Formwork\Cms\App;
use Formwork\Plugins\Controllers\AssetsController;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;

class Plugin
{
    /**
     * Whether the plugin has been initialized
     */
    protected bool $initialized = false;

    public function __construct(
        protected string $path,
        protected App $app,
        protected ViewFactory $viewFactory
    ) {}

    /**
     * Get the plugin path
     */
    final public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the plugin name
     */
    final public function name(): string
    {
        return basename($this->path());
    }

    /**
     * Get the plugin namespace
     */
    final public function namespace(): string
    {
        return "plugin:{$this->name()}";
    }

    /**
     * Method called when the plugin is initialized
     */
    final public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->loadSchemes();
        $this->loadTranslations();
        $this->loadViews();
        $this->loadAssets();

        $this->initialized = true;
    }

    /**
     * Return whether the plugin has been initialized
     */
    final public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get the plugin autoloader
     */
    public function autoload(): ?ClassLoader
    {
        return null;
    }

    /**
     * Get the event listeners defined in the plugin and their corresponding methods
     *
     * @return array<string, string>
     */
    public function getEventListeners(): array
    {
        $handlers = [];

        foreach (get_class_methods($this) as $method) {
            if (Str::startsWith($method, 'on')) {
                $eventName = lcfirst(Str::after($method, 'on'));
                $handlers[$eventName] = $method;
            }
        }

        return $handlers;
    }

    /**
     * Load plugin schemes
     */
    protected function loadSchemes(): void
    {
        $schemesPath = FileSystem::joinPaths($this->path(), 'schemes');
        if (FileSystem::isDirectory($schemesPath, assertExists: false)) {
            $this->app->schemes()->loadFromPath($schemesPath);
        }
    }

    /**
     * Load plugin translations
     */
    protected function loadTranslations(): void
    {
        $translationsPath = FileSystem::joinPaths($this->path(), 'translations');
        if (FileSystem::isDirectory($translationsPath, assertExists: false)) {
            $this->app->translations()->loadFromPath($translationsPath);
        }
    }

    /**
     * Load plugin views
     */
    protected function loadViews(): void
    {
        $viewsPath = FileSystem::joinPaths($this->path(), 'views');
        if (FileSystem::isDirectory($viewsPath, assertExists: false)) {
            $this->viewFactory->setResolutionPaths([$this->namespace() => $viewsPath]);
        }
    }

    /**
     * Load plugin assets
     */
    protected function loadAssets(): void
    {
        $assetsPath = FileSystem::joinPaths($this->path(), 'assets');
        if (FileSystem::isDirectory($assetsPath, assertExists: false)) {
            $this->app->assets()->setResolutionPaths([
                $this->namespace() => [
                    'path' => $assetsPath,
                    'uri'  => $this->app->site()->uri("/plugins/{$this->name()}/assets", includeLanguage: false),
                ],
            ]);

            $this->app->router()->addRoute("{$this->namespace()}.assets", "/plugins/{$this->name()}/assets/{type:alpha}/{file:all}")
                ->methods('GET')
                ->action(AssetsController::class . '@asset')
                ->actionParameters(['plugin' => $this]);
        }
    }
}
