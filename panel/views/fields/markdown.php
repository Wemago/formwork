<?php $this->layout('fields.field') ?>

<?php $this->modals()->addMultiple(['images', 'link']) ?>

<div class="editor-wrap">
    <div class="editor-toolbar">
        <button type="button" class="button toolbar-button editor-toggle-markdown" data-command="toggle-markdown" title="<?= $this->translate('panel.editor.toggleMarkdown') ?>" aria-label="<?= $this->translate('panel.editor.toggleMarkdown') ?>"><?= $this->icon('markdown') ?></button>
    </div>
    <textarea <?= $this->attr([
                    'class'         => ['form-textarea', 'editor-textarea'],
                    'id'            => $field->name(),
                    'name'          => $field->formName(),
                    'placeholder'   => $field->placeholder(),
                    'minlength'     => $field->minLength(),
                    'maxlength'     => $field->maxLength(),
                    'autocomplete'  => $field->autocomplete() ? 'on' : 'off',
                    'spellcheck'    => $field->spellcheck() ? 'true' : 'false',
                    'rows'          => $field->rows(),
                    'required'      => $field->isRequired(),
                    'disabled'      => $field->isDisabled(),
                    'hidden'        => $field->isHidden(),
                    'data-base-uri' => $field?->parent()?->model()?->uri(),
                ]) ?>><?= $this->escape($field->value() ?? '') ?></textarea>
</div>