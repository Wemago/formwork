<div class="files-item" data-filename="<?= $file->name() ?>" data-href="<?= $this->uri($app->router()->generate('panel.files.edit', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>">
    <?php if ($file->type() === 'image') : ?>
        <img class="file-thumbnail" src="<?= $file->square(300, 'contain')->uri() ?>" loading="lazy">
    <?php endif ?>
    <?php if ($file->type() === 'video') : ?>
        <video class="file-thumbnail" src="<?= $file->uri() ?>" preload="metadata"></video>
    <?php endif ?>
    <div class="files-item-cell file-icon"><?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?></div>
    <div class="files-item-cell file-name truncate"><?= $file->name() ?></div>
    <?php if (in_array('parent', $columns, true)) : ?>
        <div class="files-item-cell file-parent truncate show-from-lg">
            <?php if ($model->getModelIdentifier() === 'page'): ?>
                <a class="link-secondary" href="<?= $panel->uri('/pages/' . trim($model->route(), '/') . '/edit/') ?>"><span class="mr-2"><?= $this->icon($model->icon()) ?></span><span class="file-parent-title"><?= $this->escape($model->title()) ?></span></a>
            <?php endif ?>
            <?php if ($model->getModelIdentifier() === 'site') : ?>
                <a class="link-secondary" href="<?= $panel->uri('/files/') ?>"><span class="mr-2"><?= $this->icon('globe') ?></span><span class="file-parent-title"><?= $this->translate('panel.options.site') ?></span></a>
            <?php endif ?>
        </div>
    <?php endif ?>
    <?php if (in_array('date', $columns, true)) : ?>
        <div class="files-item-cell file-date truncate show-from-lg"><?= $this->datetime($file->lastModifiedTime()) ?></div>
    <?php endif ?>
    <?php if (in_array('size', $columns, true)) : ?>
        <div class="files-item-cell file-size truncate show-from-lg"><?= $file->size() ?></div>
    <?php endif ?>
    <div class="files-item-cell file-actions">
        <div class="dropdown">
            <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.files.actions') ?>" aria-label="<?= $this->translate('panel.files.actions') ?>" data-dropdown="dropdown-<?= $file->hash() ?>"><?= $this->icon('ellipsis-v') ?></button>
            <div class="dropdown-menu" id="dropdown-<?= $file->hash() ?>">
                <a class="dropdown-item" data-command="infoFile" href="<?= $this->uri($app->router()->generate('panel.files.edit', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>"><?= $this->icon('info-circle') ?> <?= $this->translate('panel.files.info') ?></a>
                <a class="dropdown-item" data-command="previewFile" href="<?= $file->uri() ?>" target="formwork-preview-file-<?= $file->hash() ?>"><?= $this->icon('eye') ?> <?= $this->translate('panel.pages.previewFile') ?></a>
                <?php if ($panel->user()->permissions()->has('pages.renameFiles')) : ?>
                    <a class="dropdown-item" data-command="renameFile" data-modal="renameFileItemModal" data-action="<?= $this->uri($app->router()->generate('panel.files.rename', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>"><?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.renameFile') ?></a>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('pages.replaceFiles')) : ?>
                    <a class="dropdown-item" data-command="replaceFile" data-action="<?= $this->uri($app->router()->generate('panel.files.replace', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>" data-mimetype="<?= $file->mimeType() ?>"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.pages.replaceFile') ?></a>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('pages.deleteFiles')) : ?>
                    <a class="dropdown-item" data-command="deleteFile" data-modal="deleteFileItemModal" data-action="<?= $this->uri($app->router()->generate('panel.files.delete', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>"><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deleteFile') ?></a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
