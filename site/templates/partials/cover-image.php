<?php if ($page->has('coverImage') && ($image = $page->coverImage())) : ?>
    <div class="cover-image" style="background-image:url(<?= $image->uri() ?>);"></div>
<?php endif ?>