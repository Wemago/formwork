<?php $this->layout('fields.field') ?>
<div class="input-wrap">
    <input <?= $this->attr([
        'type'        => 'text',
        'class'       => 'input-date',
        'id'          => $field->name(),
        'name'        => $field->formName(),
        'value'       => $field->value(),
        'placeholder' => $field->placeholder(),
        'required'    => $field->isRequired(),
        'disabled'    => $field->isDisabled(),
        'hidden'      => $field->isHidden(),
    ]) ?>>
    <span class="input-reset" data-reset="<?= $field->name() ?>"><?= $this->icon('times-circle') ?></span>
</div>
