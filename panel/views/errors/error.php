<!DOCTYPE html>
<html class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $title ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->get('images/icon.svg')->uri() ?>">
    <link rel="alternate icon" href="<?= $this->assets()->get('images/icon.png')->uri() ?>">
    <link rel="stylesheet" href="<?= $this->assets()->get('css/panel.min.css')->uri(includeVersion: true) ?>">
</head>

<body>
    <main>
        <div class="container-full">
            <div class="error-container">
                <h1>
                    <span class="error-code"><?= $code ?></span>
                    <span class="error-status"><?= $status ?></span>
                </h1>
                <img class="logo" src="<?= $this->assets()->get('images/icon.svg')->uri() ?>">
                <h2><?= $heading ?></h2>
                <p><?= $description ?></p>
                <?php if (isset($action)) : ?><a class="action" href="<?= $action['href'] ?>"><?= $action['label'] ?></a><?php endif ?>
            </div>
        </div>
        <?php if (isset($throwable)) : ?>
            <div class="container-full">
                <div class="error-debug-details">
                    <h3>Uncaught <code><?= $throwable::class ?></code>: <?= $throwable->getMessage() ?></h3>
                    <details open>
                        <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->get('system.debug.editorUri'), ['filename' => $throwable->getFile(), 'line' => $throwable->getLine()]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $throwable->getFile()) ?></span><span class="error-debug-line">:<?= $throwable->getLine() ?></span></a></summary>
                        <?= Formwork\Debug\CodeDumper::dumpLine($throwable->getFile(), $throwable->getLine(), $app->config()->get('system.debug.contextLines', 5)) ?>
                    </details>
                    <?php foreach ($throwable->getTrace() as $frame) : ?>
                        <?php if (isset($frame['file'], $frame['line']) && $frame['file'] !== $throwable->getFile() && $frame['line'] !== $throwable->getLine()) : ?>
                            <details>
                                <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->get('system.debug.editorUri'), ['filename' => $frame['file'], 'line' => $frame['line']]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $frame['file']) ?></span><span class="error-debug-line">:<?= $frame['line'] ?></span></a></summary>
                                <?= Formwork\Debug\CodeDumper::dumpBacktraceFrame($frame, $app->config()->get('system.debug.contextLines', 5)) ?>
                            </details>
                        <?php endif ?>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endif ?>
    </main>
</body>

</html>