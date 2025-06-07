<?php

namespace Formwork\Panel\Navigation;

use Formwork\Data\AbstractCollection;

class NavigationItemCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = NavigationItem::class;

    protected bool $mutable = true;
}
