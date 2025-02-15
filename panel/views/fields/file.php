<?php if ($field->has('label')): ?>
<label><?= $field->label() ?></label>
<?php endif ?>
<input <?= $this->attr([
    'type'             => 'file',
    'class'            => 'input-file',
    'id'               => $field->name(),
    'name'             => $field->formName() . ($field->get('multiple') ? '[]' : ''),
    'accept'           => $field->get('accept', implode(', ', $app->config()->get('system.files.allowedExtensions'))),
    'data-auto-upload' => $field->get('autoUpload') ? 'true' : 'false',
    'multiple'         => $field->get('multiple'),
    'required'         => $field->isRequired(),
    'disabled'         => $field->isDisabled(),
    'hidden'           => $field->isHidden(),
]) ?>>
<label for="<?= $field->name() ?>" class="input-file-label">
    <span><?= $this->icon('cloud-upload') ?> <?= $this->translate('fields.file.uploadLabel') ?></span>
</label>
<?= $this->insert('fields.partials.description') ?>
