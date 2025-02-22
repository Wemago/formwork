<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <span class="form-input-icon"><?= $this->icon($field->get('icon', 'tag')) ?></span>
    <input <?= $this->attr([
                'class'          => ['form-input', 'form-input-tags'],
                'type'           => 'text',
                'id'             => $field->name(),
                'name'           => $field->formName(),
                'value'          => implode(', ', (array) $field->value()),
                'placeholder'    => $field->placeholder(),
                'required'       => $field->isRequired(),
                'disabled'       => $field->isDisabled(),
                'hidden'         => $field->isHidden(),
                'data-limit'     => $field->limit(),
                'data-options'   => $field->options() ? Formwork\Parsers\Json::encode($field->options()) : null,
                'data-accept'    => $field->accept(),
                'data-orderable' => $field->isOrderable(),
            ]) ?>>
</div>