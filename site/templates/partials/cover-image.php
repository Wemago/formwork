<?php if ($page->has('coverImage') && ($image = $page->coverImage())) : ?>
    <div class="container">
        <img class="cover-image" src="<?= $image->uri() ?>">
    </div>
<?php endif ?>
