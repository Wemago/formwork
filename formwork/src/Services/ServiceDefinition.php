<?php

namespace Formwork\Services;

use Exception;
use Formwork\Utils\Arr;

class ServiceDefinition
{
    protected string $name;

    protected ?object $object;

    protected Container $container;

    protected array $parameters = [];

    protected ?string $loader = null;

    protected bool $lazy = true;

    public function __construct(string $name, ?object $object, Container $container)
    {
        $this->name = $name;
        $this->object = $object;
        $this->container = $container;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getObject(): ?object
    {
        return $this->object;
    }

    public function parameter(string $name, $value): self
    {
        Arr::set($this->parameters, $name, $value);
        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function loader(string $className): self
    {
        if (isset($this->object)) {
            throw new Exception('Instantiated object cannot have loaders');
        }
        $this->loader = $className;
        return $this;
    }

    public function getLoader(): ?string
    {
        return $this->loader;
    }

    public function alias(string $alias): self
    {
        $this->container->alias($alias, $this->name);
        return $this;
    }

    public function lazy(bool $lazy): self
    {
        $this->lazy = $lazy;

        if (
            $this->lazy === false
            && $this->container->has($this->name)
            && !$this->container->isResolved($this->name)
        ) {
            $this->container->resolve($this->name);
        }

        return $this;
    }
}
