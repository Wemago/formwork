<?php

namespace Formwork\Fields;

use Formwork\Data\AbstractCollection;
use Formwork\Fields\Layout\Layout;
use Formwork\Utils\Arr;

class FieldCollection extends AbstractCollection
{
    protected bool $associative = true;

    protected ?string $dataType = Field::class;

    protected bool $mutable = true;

    /**
     * Fields layout
     */
    protected Layout $layout;

    public function setLayout(Layout $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Return fields layout
     */
    public function layout(): Layout
    {
        return $this->layout;
    }

    /**
     * @inheritdoc
     */
    public function pluck(string $key, $default = null): array
    {
        return $this->everyItem()->get($key, $default)->toArray();
    }

    /**
     * Validate every field in the collection
     */
    public function validate(): static
    {
        $this->everyItem()->validate();
        return $this;
    }

    /**
     * Return whether every field in the collection is valid
     */
    public function isValid(): bool
    {
        return $this->every(fn ($field) => $field->isValid());
    }

    /**
     * Return whether every field in the collection has been validated
     */
    public function isValidated(): bool
    {
        return $this->every(fn ($field) => $field->isValidated());
    }

    /**
     * Set field values from the given data
     *
     * If the `$default` argument is given, it will be used instead of missing data
     */
    public function setValues($data, $default = null): static
    {
        $data = Arr::from($data);

        foreach ($this as $field) {
            if (Arr::has($data, $field->name())) {
                $field->set('value', Arr::get($data, $field->name()));
            } elseif (func_num_args() === 2) {
                $field->set('value', $default);
            }
        }

        return $this;
    }
}
