<?php

namespace Formwork\Services\Loaders;

use Formwork\Config\Config;
use Formwork\Plugins\PluginFactory;
use Formwork\Plugins\Plugins;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;

final class PluginsServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function load(Container $container): Plugins
    {
        $container->define(PluginFactory::class);
        return $container->build(Plugins::class);
    }

    /**
     * @param Plugins $service
     */
    public function onResolved(object $service, Container $container): void
    {
        if (!$this->config->get('system.plugins.enabled')) {
            return;
        }

        $path = $this->config->get('system.plugins.path');

        foreach ($this->config->get('plugins', []) as $name => $config) {
            $service->load($name, FileSystem::joinPaths($path, Str::toDashCase($name)));
        }
    }
}
