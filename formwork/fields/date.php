<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Utils\Constraint;
use Formwork\Utils\Date;
use Formwork\Utils\Str;

return function (App $app): array {
    return [
        'format' => function (Field $field, ?string $format = null, string $type = 'pattern') use ($app): string {
            $format ??= $app->config()->get('system.date.dateFormat');
            $translation = $app->translations()->getCurrent();

            if ($format !== null) {
                $format = match (strtolower($type)) {
                    'pattern' => Date::patternToFormat($format),
                    'date'    => $format,
                    default   => throw new InvalidArgumentException('Invalid date format type')
                };
            }
            return $field->isEmpty() ? '' : Date::formatTimestamp($field->toTimestamp(), $format, $translation);
        },

        'toTimestamp' => function (Field $field) use ($app): ?int {
            $formats = [
                $app->config()->get('system.date.dateFormat'),
                $app->config()->get('system.date.datetimeFormat'),
            ];
            return $field->isEmpty() ? null : Date::toTimestamp($field->value(), $formats);
        },

        'toDuration' => function (Field $field) use ($app): string {
            return $field->isEmpty() ? '' : Date::formatTimestampAsDistance($field->toTimestamp(), $app->translations()->getCurrent());
        },

        'toString' => function (Field $field): string {
            return $field->isEmpty() ? '' : $field->format();
        },

        'return' => function (Field $field): Field {
            return $field;
        },

        'validate' => function (Field $field, $value) use ($app): ?string {
            if (Constraint::isEmpty($value)) {
                return null;
            }

            $formats = [
                $app->config()->get('system.date.dateFormat'),
                $app->config()->get('system.date.datetimeFormat'),
            ];

            try {
                return date('Y-m-d H:i:s', Date::toTimestamp($value, $formats));
            } catch (InvalidArgumentException $e) {
                throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s":%s', $field->name(), $field->type(), Str::after($e->getMessage(), ':')));
            }
        },
    ];
};
