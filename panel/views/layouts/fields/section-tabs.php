<div class="tabs" role="tablist">
    <?php foreach ($layout->tabs() as $tab): ?>
        <button type="button" class="<?= $this->classes(['button', 'button-link', 'tabs-tab', 'active' => $tab === $layout->tabs()->first()]) ?>" id="tab-<?= $this->escapeAttr($tab->name()) ?>" role="tab" aria-selected="<?= $tab === $layout->tabs()->first() ? 'true' : 'false' ?>" data-tab="<?= $this->escapeAttr($tab->name()) ?>"><?= $this->escape($tab->label()) ?></button>
    <?php endforeach ?>
</div>
<div class="tabs-content">
    <?= $this->content() ?>
</div>