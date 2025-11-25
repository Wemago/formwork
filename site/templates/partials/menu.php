<nav class="menu">
    <a class="menu-header" href="<?= $site->uri() ?>"><?= $this->escape($site->title()) ?></a>
    <div class="menu-toggle">
        <button type="button" class="button menu-toggle" data-toggle="main-menu" aria-expanded="false"><?= $this->assets()->get('icons/svg/bars.svg')->content() ?></button>
    </div>
    <div class="menu-list menu-collapse" id="main-menu">
        <?php foreach ($site->children()->published()->listed()->routable() as $item) : ?>
            <a class="<?= $this->classes(['menu-item', 'active' => $item->isCurrent() || $site->currentPage()?->isDescendantOf($item)]) ?>" href="<?= $item->uri() ?>"><?= $this->escape($item->get('menu', $item->title())) ?></a>
        <?php endforeach ?>
    </div>
</nav>