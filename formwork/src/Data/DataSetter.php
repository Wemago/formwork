<?php

namespace Formwork\Data;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Data\Traits\DataMultipleGetter;
use Formwork\Data\Traits\DataMultipleSetter;

final class DataSetter implements Arrayable
{
    use DataArrayable;
    use DataMultipleGetter;
    use DataMultipleSetter;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return whether data is present
     */
    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /**
     * Create an instance from another getter
     */
    public static function fromGetter(DataGetter|DataSetter $getter): self
    {
        return new self($getter->toArray());
    }
}
