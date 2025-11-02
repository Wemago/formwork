<?php

namespace Formwork\Plugins;

use Formwork\Config\Config;
use Formwork\Events\EventDispatcher;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use InvalidArgumentException;

class Plugins
{
    /**
     * @var array<string, Plugin>
     */
    protected array $storage = [];

    /**
     * @var array<string, string>
     */
    protected array $data = [];

    public function __construct(
        private Config $config,
        private PluginFactory $pluginFactory,
        private EventDispatcher $eventDispatcher
    ) {}

    /**
     * Load a plugin
     */
    public function load(string $id, string $path): void
    {
        $this->data[$id] = $path;
        unset($this->storage[$id]);
    }

    /**
     * Load plugin files from a path
     */
    public function loadFromPath(string $path): void
    {
        foreach (FileSystem::listContents($path) as $item) {
            $id = Str::toCamelCase($item);
            $this->load($id, FileSystem::joinPaths($path, $item));
        }
    }

    /**
     * Return whether a plugin exists from id
     */
    public function has(string $id): bool
    {
        return isset($this->data[$id]);
    }

    /**
     * Initialize a plugin from id
     *
     * @throws InvalidArgumentException If the plugin id is invalid
     */
    public function initialize(string $id): void
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(sprintf('Invalid plugin "%s"', $id));
        }

        $plugin = $this->pluginFactory->make($this->data[$id]);

        $plugin->autoload()?->register();

        $plugin->initialize();

        foreach ($plugin->getEventListeners() as $eventName => $method) {
            $this->eventDispatcher->on($eventName, $plugin->{$method}(...));
        }

        $this->storage[$id] = $plugin;
    }

    /**
     * Initialize all enabled plugins
     */
    public function initializeEnabled(): void
    {
        foreach (array_keys($this->data) as $id) {
            if (!$this->config->get("plugins.{$id}.enabled")) {
                continue;
            }

            $this->initialize($id);
        }
    }

    /**
     * Get a plugin from id
     */
    public function get(string $id): Plugin
    {
        if (isset($this->storage[$id])) {
            return $this->storage[$id];
        }

        $this->initialize($id);
        return $this->storage[$id];
    }
}
