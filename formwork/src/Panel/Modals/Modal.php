<?php

namespace Formwork\Panel\Modals;

use Formwork\Data\Contracts\Arrayable;
use Formwork\Data\Traits\DataArrayable;
use Formwork\Fields\FieldCollection;
use Formwork\Fields\FieldFactory;
use Formwork\Translations\Translation;
use Formwork\Utils\Arr;
use Formwork\Utils\Str;
use UnexpectedValueException;

class Modal implements Arrayable
{
    use DataArrayable;

    /**
     * Modal identifier
     */
    protected string $id;

    /**
     * Modal buttons
     */
    protected ModalButtonCollection $buttons;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        string $id,
        array $data,
        protected Translation $translation,
        protected FieldFactory $fieldFactory,
    ) {
        $this->id = Str::append($id, 'Modal');

        $this->data = [...$this->defaults(), ...$data];

        if ($this->data['title'] === null) {
            throw new UnexpectedValueException('Unexpected missing title');
        }

        $this->translate();
    }

    /**
     * Get modal id
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get modal title
     */
    public function title(): string
    {

        return $this->data['title'];
    }

    /**
     * Get modal message
     */
    public function message(): ?string
    {
        return $this->data['message'] ?? null;
    }

    /**
     * Get modal action
     */
    public function action(): ?string
    {
        return $this->data['action'] ?? null;
    }

    /**
     * Get modal size
     */
    public function size(): string
    {
        return $this->data['size'];
    }

    /**
     * Get modal fields
     */
    public function fields(): FieldCollection
    {
        $fieldCollection = new FieldCollection();

        // @phpstan-ignore argument.templateType
        $fieldCollection->setMultiple(Arr::map($this->data['fields'] ?? [], fn($data, $name) => $this->fieldFactory->make($this->id . '.' . $name, $data, $fieldCollection)));

        return $fieldCollection;
    }

    /**
     * Get modal buttons
     */
    public function buttons(): ModalButtonCollection
    {
        if (!isset($this->buttons)) {
            $this->buttons = new ModalButtonCollection(Arr::map($this->data['buttons'] ?? [], fn(array $data) => new ModalButton($data, $this->translation)));
        }
        return $this->buttons;
    }

    /**
     * Return whether the modal has a form
     */
    public function hasForm(): bool
    {
        return $this->data['form'] ?? true;
    }

    /**
     * Get  default modal options
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'title'   => null,
            'message' => null,
            'action'  => null,
            'size'    => 'small',
        ];
    }

    /**
     * Translate modal title and message
     */
    protected function translate(): void
    {
        $this->data['title'] = Str::interpolate($this->data['title'], fn($key) => $this->translation->translate($key));

        if (isset($this->data['message'])) {
            $this->data['message'] = Str::interpolate($this->data['message'], fn($key) => $this->translation->translate($key));
        }
    }
}
