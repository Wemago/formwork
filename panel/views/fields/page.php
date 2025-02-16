<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <span class="form-input-icon"><?= $this->icon($field->get('icon', 'page')) ?></span>
    <select <?= $this->attr([
                'class'    => 'form-select',
                'id'       => $field->name(),
                'name'     => $field->formName(),
                'required' => $field->isRequired(),
                'disabled' => $field->isDisabled(),
                'hidden'   => $field->isHidden(),
            ]) ?>>
        <?php if (!$field->isRequired()) : ?>
            <option <?= $this->attr([
                        'value' => '',
                        'selected' => $field->value() === '',
                    ]) ?>><?= $this->translate('page.none') ?></option>
        <?php endif ?>
        <?php if ($field->get('allowSite')) : ?>
            <option <?= $this->attr([
                        'value' => '.',
                        'selected' => $field->value() === '.',
                    ]) ?>><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
        <?php endif ?>
        <?php foreach ($field->collection() as $page) : ?>
            <option <?= $this->attr([
                        'value' => $page->route(),
                        'selected' => $page->route() === $field->value(),
                        'data-allowed-templates' => $page->scheme()->options()->get('children.templates'),
                    ]) ?>><?= str_repeat('— ', $page->level() - 1) . $this->escape($page->title()) ?></option>
        <?php endforeach ?>
    </select>
</div>