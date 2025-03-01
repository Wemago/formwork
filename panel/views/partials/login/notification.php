<?php if ($notification = $panel->notifications()) : ?>
    <?php if (($type = $notification[0]['type']) === 'error') : ?>
        <div class="login-modal-danger"><?= $this->icon('exclamation-octagon') ?> <?= $this->escape($notification[0]['text']) ?></div>
    <?php elseif ($type === 'warning') : ?>
        <div class="login-modal-warning"><?= $this->icon('exclamation-triangle') ?> <?= $this->escape($notification[0]['text']) ?></div>
    <?php elseif ($type === 'success') : ?>
        <div class="login-modal-success"><?= $this->icon('check-circle') ?> <?= $this->escape($notification[0]['text']) ?></div>
    <?php else : ?>
        <div class="login-modal-info"><?= $this->icon('info-circle') ?> <?= $this->escape($notification[0]['text']) ?></div>
    <?php endif ?>
<?php endif ?>
