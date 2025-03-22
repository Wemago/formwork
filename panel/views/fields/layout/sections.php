<div class="sections">
    <?php foreach ($sections as $section) : ?>
        <section <?= $this->attr(['class' => ['section',  $section->is('collapsible') ? 'collapsible' : '', $section->is('collapsed') ? 'collapsed' : '']]) ?>>
            <div class="section-header">
                <?php if ($section->is('collapsible')) : ?>
                    <button type="button" class="button section-toggle mr-2" title="<?= $this->translate('panel.sections.toggle') ?>" aria-label="<?= $this->translate('panel.sections.toggle') ?>"><?= $this->icon('chevron-up') ?></button>
                <?php endif ?>
                <span class="caption"><?= $this->escape($section->label()) ?></span>
            </div>
            <div class="section-content">
                <div class="row">
                    <?php foreach ($fields->getMultiple($section->get('fields', [])) as $field) : ?>
                        <?php if ($field->isVisible()) : ?>
                            <div <?= $this->attr(['class' => ['col-md-' . $field->get('width', '12-12')]]) ?>>
                                <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
                            </div>
                        <?php endif ?>
                    <?php endforeach ?>
                </div>
            </div>
        </section>
    <?php endforeach ?>
</div>
