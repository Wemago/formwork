<?php

use Formwork\Cms\App;
use Formwork\Fields\Exceptions\ValidationException;
use Formwork\Fields\Field;
use Formwork\Files\FileCollection;
use Formwork\Http\Files\UploadedFile;
use Formwork\Utils\Arr;
use Formwork\Utils\Constraint;
use Formwork\Utils\MimeType;

return function (App $app) {
    return [
        'methods' => [
            /**
             * Return the accepted MIME types for the field
             */
            'acceptMimeTypes' => function (Field $field) use ($app) {
                $allowedExtensions = $app->config()->get('system.files.allowedExtensions', '');

                $accept = is_string($field->get('accept'))
                    ? preg_split('/\s*,\s*/', $field->get('accept'), flags: PREG_SPLIT_NO_EMPTY)
                    : $field->get('accept', $allowedExtensions);

                return Arr::map($accept, MimeType::fromExtension(...));
            },

            /**
             * Return the collection of files associated with the field
             */
            'collection' => function (Field $field): FileCollection {
                return $field->get('collection', new FileCollection());
            },

            /**
             * Return whether the field is set to auto-upload files
             */
            'autoUpload' => function (Field $field): bool {
                return $field->is('autoUpload');
            },

            /**
             * Return whether the field accepts multiple files
             */
            'isMultiple' => function (Field $field): bool {
                return $field->is('multiple');
            },

            /**
             * Return the destination path for uploaded files
             */
            'destination' => function (Field $field): ?string {
                return $field->get('destination');
            },

            /**
             * Return whether the field allows overwriting existing files
             */
            'overwrite' => function (Field $field): bool {
                return $field->is('overwrite');
            },

            /**
             * Return the filename for the uploaded file
             *
             * This is only applicable when the field does not allow multiple files.
             */
            'filename' => function (Field $field): ?string {
                return $field->get('filename');
            },

            'validate' => function (Field $field, $value) use ($app) {
                if (Constraint::isEmpty($value)) {
                    return null;
                }

                $allowedExtensions = $app->config()->get('system.files.allowedExtensions', '');
                $allowedMimeTypes = Arr::map($allowedExtensions, MimeType::fromExtension(...));
                $acceptMimeTypes = $field->acceptMimeTypes();

                if (($unallowedMimeTypes = array_diff($acceptMimeTypes, $allowedMimeTypes)) !== []) {
                    throw new ValidationException(sprintf('Invalid accept attribute for field "%s" of type "%s". Found unallowed MIME types: %s', $field->name(), $field->type(), implode(', ', $unallowedMimeTypes)));
                }

                if (!$field->isMultiple()) {
                    if (!($value instanceof UploadedFile)) {
                        throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s". Expected an instance of %s', $field->name(), $field->type(), UploadedFile::class));
                    }

                    if ($value->isEmpty()) {
                        if ($field->isRequired()) {
                            throw new ValidationException(sprintf('Required field "%s" of type "%s" cannot be empty', $field->name(), $field->type()));
                        }
                        return null;
                    }

                    return $value;
                }

                if (!is_array($value)) {
                    throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s". Expected an array of %s', $field->name(), $field->type(), UploadedFile::class));
                }

                if ($field->filename() !== null) {
                    throw new ValidationException(sprintf('Field "%s" of type "%s" cannot have a filename set when multiple files are allowed', $field->name(), $field->type()));
                }

                $value = Arr::filter($value, function ($file) use ($field) {
                    if (!($file instanceof UploadedFile)) {
                        throw new ValidationException(sprintf('Invalid value for field "%s" of type "%s". Expected an instance of %s', $field->name(), $field->type(), UploadedFile::class));
                    }

                    return $file->isUploaded();
                });

                if ($value === []) {
                    if ($field->isRequired()) {
                        throw new ValidationException(sprintf('Required field "%s" of type "%s" cannot be empty', $field->name(), $field->type()));
                    }
                    return null;
                }

                return $value;
            },
        ],
    ];
};
