</div>
<div class="error-debug-details">
    <h3>Uncaught <code><?= $throwable::class ?></code>: <?= $throwable->getMessage() ?></h3>
    <details open>
        <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->get('system.debug.editorUri'), ['filename' => $throwable->getFile(), 'line' => $throwable->getLine()]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $throwable->getFile()) ?></span><span class="error-debug-line">:<?= $throwable->getLine() ?></span></a></summary>
        <?= Formwork\Debug\CodeDumper::dumpLine($throwable->getFile(), $throwable->getLine(), $app->config()->get('system.debug.contextLines', 5)) ?>
    </details>
    <?php foreach ($throwable->getTrace() as $frame) : ?>
        <?php if (isset($frame['file'], $frame['line']) && ($frame['file'] !== $throwable->getFile() || $frame['line'] !== $throwable->getLine())): ?>
            <details>
                <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->get('system.debug.editorUri'), ['filename' => $frame['file'], 'line' => $frame['line']]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $frame['file']) ?></span><span class="error-debug-line">:<?= $frame['line'] ?></span></a></summary>
                <?= Formwork\Debug\CodeDumper::dumpBacktraceFrame($frame, $app->config()->get('system.debug.contextLines', 5)) ?>
            </details>
        <?php endif ?>
    <?php endforeach ?>