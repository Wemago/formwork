<div class="tabs">
<?php
    foreach ($tabs as $tab):
        if ($this->user()->permissions()->has('options.' . $tab)) :
?>
    <a class="tabs-tab<?= ($tab === $current) ? ' active' : '' ?>" href="<?= $this->uri('/options/' . $tab . '/') ?>"><?= $this->label('options.' . $tab) ?></a>
<?php
        endif;
    endforeach;
?>
</div>
