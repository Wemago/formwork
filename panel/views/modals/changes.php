<div id="changesModal" class="modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="caption"><?= $this->translate('panel.pages.changes.detected') ?></h3>
        </div>
        <div class="modal-content">
            <p class="modal-text"><?= $this->translate('panel.pages.changes.detected.prompt') ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" data-dismiss="changesModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="button" class="button-accent button-right" data-command="continue" data-href="#"><?= $this->icon('exclamation-circle') ?> <?= $this->translate('panel.pages.changes.continue') ?></button>
        </div>
    </div>
</div>
