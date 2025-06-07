<?php foreach ($this->assets()->scripts() as $script): ?>
    <script src="<?= $script->uri(includeVersion: true) ?>" integrity="<?= $script->integrityHash() ?>"></script>
<?php endforeach ?>

<script>
    Formwork.app.load(<?= Formwork\Parsers\Json::encode($panel->getAppConfig()) ?>);
</script>