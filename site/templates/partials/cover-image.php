<?php if ($page->has('image') && !$page->image()->isEmpty() && $page->images()->has($page->image())): ?>
    <div class="cover-image" style="background-image:url(<?= $page->uri($page->image()) ?>);"></div>
<?php endif ?>
