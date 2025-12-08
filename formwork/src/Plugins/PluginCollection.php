<?php

namespace Formwork\Plugins;

use Formwork\Data\AbstractCollection;

class PluginCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Plugin::class;
}
