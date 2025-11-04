<?php

namespace Formwork\Plugins;

use Composer\Autoload\ClassLoader;
use Formwork\Cms\App;
use Formwork\Utils\Str;
use Formwork\View\ViewFactory;

class Plugin
{
    /**
     * Whether the plugin has been initialized
     */
    protected bool $initialized = false;

    public function __construct(protected string $path, protected App $app, protected ViewFactory $viewFactory) {}

    /**
     * Get the plugin path
     */
    final public function path(): string
    {
        return $this->path;
    }

    /**
     * Method called when the plugin is initialized
     */
    public function initialize(): void
    {
        $this->initialized = true;
        $this->viewFactory->setResolutionPaths([
            'plugin:' . basename($this->path()) => $this->path() . '/views/',
        ]);
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
}
