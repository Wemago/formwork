<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;

use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'validate' => function (Field $field, $value): string {
            if (Constraint::isEmpty($value)) {
                return '';
            }

            if (!is_string($value) && !is_numeric($value)) {
                throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
            }

            if ($field->has('min') && strlen((string) $value) < $field->get('min')) {
                throw new ValidationException(sprintf('The minimum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('min')));
            }

            if ($field->has('max') && strlen((string) $value) > $field->get('max')) {
                throw new ValidationException(sprintf('The maximum allowed length for field "%s" of type "%s" is %d', $field->name(), $field->value(), $field->get('max')));
            }

            if ($field->has('pattern') && !Constraint::matchesRegex((string) $value, $field->get('pattern'))) {
                throw new ValidationException(sprintf('The value of field "%s" of type "%s" does not match the required pattern', $field->name(), $field->value()));
            }

            return (string) $value;
        },
    ];
};
