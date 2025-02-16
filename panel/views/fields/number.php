<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?php if ($field->has('icon')) : ?>
        <span class="form-input-icon"><?= $this->icon($field->get('icon')) ?></span>
    <?php endif ?>
    <input <?= $this->attr([
                'class'       => 'form-input',
                'type'        => 'number',
                'id'          => $field->name(),
                'name'        => $field->formName(),
                'min'         => $field->get('min'),
                'max'         => $field->get('max'),
                'step'        => $field->get('step'),
                'value'       => $field->value(),
                'placeholder' => $field->placeholder(),
                'required'    => $field->isRequired(),
                'disabled'    => $field->isDisabled(),
                'hidden'      => $field->isHidden(),
            ]) ?>>
</div>