<!DOCTYPE html>
<html lang="<?= $app->translations()->getCurrent()->code() ?>" class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?php foreach ($panel->notifications() as $notification) : ?>
        <meta name="notification" content='<?= $this->escapeAttr(Formwork\Parsers\Json::encode($notification)) ?>'>
    <?php endforeach ?>
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->get('images/icon.svg')->uri() ?>">
    <link rel="alternate icon" href="<?= $this->assets()->get('images/icon.png')->uri() ?>">
    <?php $this->assets()->add('css/panel.min.css') ?>
    <?php foreach ($this->assets()->stylesheets() as $stylesheet): ?>
        <link rel="stylesheet" href="<?= $stylesheet->uri(includeVersion: true) ?>">
    <?php endforeach ?>
</head>

<body>
    <?php $this->insert('partials.sidebar') ?>
    <header class="panel-header">
        <span class="show-from-sm text-color-gray-dark"><?= $this->translate('panel.panel') ?></span>
        <span class="show-from-sm ml-5 mr-2 text-color-gray-medium">/</span>
        <span class="flex-grow-1"><a class="button button-link text-size-md" href="<?= $panel->uri('/options/site/') ?>"><?= $this->icon('globe') ?> <span class="ml-2"><?= $this->escape($site->title()) ?></span></a></span>
        <a href="<?= $site->uri() ?>" class="button button-link text-size-md" target="formwork-view-site"><span class="show-from-xs"><?= $this->translate('panel.viewSite') ?></span> <?= $this->icon('arrow-right-up-box') ?></a>
    </header>
    <main class="panel-main">
        <div class="container">
            <?= $this->content() ?>
        </div>
    </main>
    <?php foreach ($this->modals() as $modal) : ?>
        <?php $this->insert('modals.modal', ['modal' => $modal]) ?>
    <?php endforeach ?>
    <?php $this->assets()->add('js/app.min.js') ?>
    <?php $this->insert('partials.scripts') ?>
</body>

</html>