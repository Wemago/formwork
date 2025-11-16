<?php

namespace Formwork\Fields\Layout;

use Formwork\Data\AbstractCollection;
use Formwork\Translations\Translation;
use Formwork\Utils\Arr;

class TabCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Tab::class;

    /**
     * @param array<string, array<string, mixed>> $tabs
     */
    public function __construct(array $tabs, Translation $translation)
    {
        parent::__construct(Arr::map($tabs, fn(array $tab, string $name) => new Tab($name, $tab, $translation)));
    }
}
