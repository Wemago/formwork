<?php

use Formwork\Cms\App;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Images\Image;
use Formwork\Utils\Constraint;

return function (App $app) {
    return [
        'return' => function (Field $field): ?Image {
            return $field->value() !== null
                ? $field->getImages()->get($field->value())
                : null;
        },

        'getImages' => function (Field $field): FileCollection {
            if (!$field->has('options')) {
                $model = $field->parent()?->model();

                if ($model === null || !method_exists($model, 'files')) {
                    throw new InvalidValueException(sprintf('Field "%s" of type "%s" must have a model with files', $field->name(), $field->type()));
                }

                $files = $model->files();
            } else {
                $files = $field->get('options');
            }

            return $files->filter(static fn(File $file) => $file instanceof Image);
        },

        'validate' => function (Field $field, $value): ?string {
            if (Constraint::isEmpty($value)) {
                return null;
            }

            if (!is_string($value)) {
                throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s"', $field->name(), $field->type()));
            }

            return $value;
        },

        'options' => function (Field $field): array {
            return $field->getImages()
                ->map(static fn(Image $image) => [
                    'value' => $image->name(),
                    'icon'  => 'image',
                    'thumb' => $image->square(300, 'contain')->uri(),
                ])->toArray();
        },
    ];
};
