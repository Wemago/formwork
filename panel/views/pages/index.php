<?php $this->layout('panel') ?>

<?php $this->modals()->add('newPage') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.pages.pages') ?> <span class="badge"><?= $app->site()->descendants()->count() ?></span></div>
    <div>
        <?php if ($panel->user()->permissions()->has('panel.pages.create')) : ?>
            <button type="button" class="button button-accent" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
        <?php endif ?>
    </div>
</div>

<section class="section">
    <div class="flex flex-wrap">
        <div class="flex-grow-1 mr-4">
            <div class="form-input-wrap">
                <span class="form-input-icon"><?= $this->icon('search') ?></span>
                <input class="form-input page-search" id="pages.search" type="search" placeholder="<?= $this->translate('panel.pages.pages.search') ?>">
            </div>
        </div>
        <div class="whitespace-nowrap">
            <button type="button" class="button button-secondary mb-4" data-command="expand-all-pages"><?= $this->icon('chevron-down') ?> <?= $this->translate('panel.pages.pages.expandAll') ?></button>
            <button type="button" class="button button-secondary mb-4" data-command="collapse-all-pages"><?= $this->icon('chevron-up') ?> <?= $this->translate('panel.pages.pages.collapseAll') ?></button>
            <button type="button" class="button button-secondary mb-4" data-command="reorder-pages"><?= $this->icon('reorder-v') ?> <?= $this->translate('panel.pages.pages.reorder') ?></button>
        </div>
    </div>
    <?= $pagesTree ?>
</section>