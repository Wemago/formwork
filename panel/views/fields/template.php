<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <span class="form-input-icon"><?= $this->icon($field->get('icon', 'template')) ?></span>
    <select <?= $this->attr([
                'class'    => 'form-select',
                'id'       => $field->name(),
                'name'     => $field->formName(),
                'required' => $field->isRequired(),
                'disabled' => $field->isDisabled(),
                'hidden'   => $field->isHidden(),
            ]) ?>>
        <?php foreach ($site->templates() as $template) : ?>
            <option value="<?= $template->name() ?>" <?php if ($template->name() === (string) $field->value()) : ?> selected<?php endif ?>><?= $this->escape($template->title()) ?></option>
        <?php endforeach ?>
    </select>
</div>