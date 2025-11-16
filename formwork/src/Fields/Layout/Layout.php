<?php

namespace Formwork\Fields\Layout;

use Formwork\Translations\Translation;

class Layout
{
    /**
     * Layout type
     */
    protected string $type;

    /**
     * Layout sections collection
     */
    protected SectionCollection $sections;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected array $data, protected Translation $translation)
    {
        $this->type = $data['type'];
    }

    /** Get layout type
     *
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get layout sections
     */
    public function sections(): SectionCollection
    {
        return $this->sections ??= (new SectionCollection(
            $this->data['sections'] ?? [],
            $this->translation,
        ))
            ->sort(sortBy: fn(Section $a, Section $b): int => $a->order() <=> $b->order());
    }
}
