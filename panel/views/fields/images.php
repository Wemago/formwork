<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <span class="form-input-icon"><?= $this->icon($field->get('icon', 'image')) ?></span>
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
                'data-options'   => Formwork\Parsers\Json::encode($field->options()),
                'data-accept'    => 'options',
                'data-orderable' => $field->is('orderable', true),
            ]) ?>>
</div>