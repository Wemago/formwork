<?php

namespace Formwork\Fields\Layout;

use Formwork\Data\AbstractCollection;
use Formwork\Translations\Translation;
use Formwork\Utils\Arr;

class SectionCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Section::class;

    /**
     * @param array<string, array<string, mixed>> $sections
     */
    public function __construct(array $sections, Translation $translation)
    {
        parent::__construct(Arr::map($sections, fn(array $section, string $name) => new Section($name, $section, $translation)));
    }
}
