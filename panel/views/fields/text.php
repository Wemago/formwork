<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?php if ($field->has('icon')) : ?>
        <span class="form-input-icon"><?= $this->icon($field->get('icon')) ?></span>
    <?php endif ?>
    <input <?= $this->attr([
                'class'       => ['form-input', $field->get('class')],
                'type'        => 'text',
                'id'          => $field->name(),
                'name'        => $field->formName(),
                'value'       => $field->value(),
                'placeholder' => $field->placeholder(),
                'minlength'   => $field->get('min'),
                'maxlength'   => $field->get('max'),
                'pattern'     => $field->get('pattern'),
                'required'    => $field->isRequired(),
                'disabled'    => $field->isDisabled(),
                'hidden'      => $field->isHidden(),
            ]) ?>>
</div>