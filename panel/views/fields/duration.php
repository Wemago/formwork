<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <span class="form-input-icon"><?= $this->icon($field->get('icon', 'hourglass')) ?></span>
    <input <?= $this->attr([
                'class'          => 'form-input',
                'type'           => 'number',
                'id'             => $field->name(),
                'name'           => $field->formName(),
                'min'            => $field->get('min'),
                'max'            => $field->get('max'),
                'step'           => $field->get('step'),
                'value'          => $field->value(),
                'required'       => $field->isRequired(),
                'disabled'       => $field->isDisabled(),
                'hidden'         => $field->isHidden(),
                'data-field'     => 'duration',
                'data-unit'      => $field->get('unit', 'seconds'),
                'data-intervals' => $field->has('intervals') ? implode(', ', $field->get('intervals')) : null,
            ]) ?>>
</div>