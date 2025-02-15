<?php

namespace Formwork\Files;

use Closure;
use Exception;
use Formwork\Services\Container;
use Formwork\Utils\FileSystem;

class FileFactory
{
    public function __construct(protected Container $container, protected array $associations = [])
    {

    }

    public function make(string $path): File
    {
        $mimeType = FileSystem::mimeType($path);

        $class = $this->associations[$mimeType] ?? File::class;

        if (is_array($class)) {
            [$class, $method] = $class;
        }

        $class = $this->container->build($class, compact('path'));

        /**
         * @var File
         */
        $instance = isset($method)
            ? $this->container->call(Closure::fromCallable($class->$method(...)), compact('path'))
            : $class;

        if (!$instance instanceof File) {
            throw new Exception(sprintf('Invalid object of type %s, only instances of %s are allowed', get_debug_type($instance), File::class));
        }

        $instance->setUriGenerator($this->container->get(FileUriGenerator::class));

        return $instance;
    }
}
