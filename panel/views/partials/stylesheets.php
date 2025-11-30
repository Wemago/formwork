<?php foreach ($this->assets()->stylesheets() as $stylesheet): ?>
    <link <?= $this->attr([
                'rel'       => 'stylesheet',
                'href'      => $stylesheet->uri(includeVersion: true),
                'title'     => $stylesheet->getMeta('title'),
                'media'     => $stylesheet->getMeta('media'),
                'blocking'  => $stylesheet->getMeta('blocking'),
                'integrity' => $stylesheet->integrityHash(),
            ]) ?>>
<?php endforeach ?>