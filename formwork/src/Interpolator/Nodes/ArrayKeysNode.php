<?php

namespace Formwork\Interpolator\Nodes;

class ArrayKeysNode extends AbstractNode
{
    /**
     * @inheritdoc
     */
    public const string TYPE = 'array keys';

    /**
     * @param list<array-key> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
