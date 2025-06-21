<?php

namespace Formwork\Data\Traits;

use Countable;

/**
 * @phpstan-require-implements Countable
 */
trait DataCountable
{
    protected array $data = [];

    /**
     * Get the number of data items
     */
    public function count(): int
    {
        return count($this->data);
    }
}
