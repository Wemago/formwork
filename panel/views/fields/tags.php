<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <span class="form-input-icon"><?= $this->icon($field->get('icon', 'tag')) ?></span>
    <input <?= $this->attr([
                'class'          => 'form-input',
                'type'           => 'text',
                'id'             => $field->name(),
                'name'           => $field->formName(),
                'value'          => implode(', ', (array) $field->value()),
                'placeholder'    => $field->placeholder(),
                'required'       => $field->isRequired(),
                'disabled'       => $field->isDisabled(),
                'hidden'         => $field->isHidden(),
                'data-field'     => 'tags',
                'data-limit'     => $field->get('limit'),
                'data-options'   => $field->has('options') ? Formwork\Parsers\Json::encode($field->options()) : null,
                'data-accept'    => $field->get('accept', 'options'),
                'data-orderable' => $field->is('orderable', true),
            ]) ?>>
</div>