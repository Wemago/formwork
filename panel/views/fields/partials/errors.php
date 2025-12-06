<?php if ($field->isValidated() && ($error = $field->getValidationError())): ?>
    <div class="form-input-errors"><?= $this->escape($error) ?></div>
<?php endif ?>