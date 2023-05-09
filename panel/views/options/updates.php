<?php $this->layout('panel') ?>

<div class="header">
        <div class="header-title"><?= $this->translate('panel.options.options') ?></div>
</div>

<?= $tabs ?>

<div id="updater-component" class="component">
    <div class="row">
        <div class="col-m-1-1">
            <div class="checker"><span class="spinner"></span><span class="update-status" data-checking-text="<?= $this->translate('panel.updates.status.checking') ?>" data-installing-text="<?= $this->translate('panel.updates.status.installing') ?>"><?= $this->translate('panel.updates.status.checking') ?></span></div>
        </div>
    </div>
    <div class="separator-l"></div>
    <div class="row new-version" style="display: none;">
        <div class="col-m-1-1">
            <div class="h5"><strong class="new-version-name">Formwork x.x.x</strong> <?= $this->translate('panel.updates.availableForInstall') ?></div>
            <div><?= $this->translate('panel.updates.installPrompt') ?></div>
            <div class="separator"></div>
            <button type="button" data-command="install-updates"><?= $this->translate('panel.updates.install') ?></button>
        </div>
    </div>
    <div class="row current-version" style="display: none;">
        <div class="col-m-1-1">
            <div class="h5"><strong class="current-version-name">Formwork <?= $currentVersion ?></strong> <?= $this->translate('panel.updates.latestVersionAvailable') ?></div>
        </div>
    </div>
</div>
