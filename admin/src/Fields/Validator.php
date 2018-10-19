<?php

namespace Formwork\Admin\Fields;

use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use DateTime;

class Validator
{
    /**
     * Fields not to validate
     *
     * @var array
     */
    protected static $ignore = array('column', 'header', 'row', 'rows');

    /**
     * Validate all Fields against given data
     */
    public static function validate(Fields $fields, DataGetter $data)
    {
        foreach ($fields as $field) {
            if ($field->has('fields')) {
                $field->get('fields')->validate($data);
            }
            if (in_array($field->type(), static::$ignore, true)) {
                continue;
            }
            $method = 'validate' . ucfirst(strtolower($field->type()));
            if (method_exists(static::class, $method)) {
                $value = static::$method($data->get($field->name()), $field);
            } else {
                $value = $data->get($field->name(), $field->get('default'));
            }
            $field->set('value', $value);
        }
    }

    /**
     * Validate checkbox field
     *
     * @param string $value
     *
     * @return bool
     */
    public static function validateCheckbox($value)
    {
        return !empty($value);
    }

    /**
     * Validate togglegroup field
     *
     * @param string $value
     */
    public static function validateTogglegroup($value)
    {
        if ($value === '0' || $value === 'false' || $value === '') {
            return false;
        }
        if ($value === '1' || $value === 'true') {
            return true;
        }
        return static::parse($value);
    }

    /**
     * Validate date field
     *
     * @param string $value
     *
     * @return string
     */
    public static function validateDate($value)
    {
        if (!empty($value)) {
            $format = Formwork::instance()->option('date.format');
            $date = date_create_from_format($format, $value);
            if ($date instanceof DateTime) {
                return date_format($date, 'Y-m-d');
            }
        }
        return $value;
    }

    /**
     * Validate number field
     *
     * @param string $value
     *
     * @return float|int
     */
    public static function validateNumber($value, &$field)
    {
        $number = static::parse($value);
        if (!is_null($value)) {
            if ($field->has('min')) {
                $number = max($number, (int) $field->get('min'));
            }
            if ($field->has('max')) {
                $number = min($number, (int) $field->get('max'));
            }
        }
        return $number;
    }

    /**
     * Validate range field
     *
     * @param string $value
     *
     * @return float|int
     */
    public static function validateRange($value, &$field)
    {
        return static::validateNumber($value, $field);
    }

    /**
     * Validate select field
     *
     * @param string $value
     */
    public static function validateSelect($value)
    {
        return static::parse($value);
    }

    /**
     * Validate tags field
     *
     * @param array|string $value
     *
     * @return array
     */
    public static function validateTags($value, &$field)
    {
        $tags = is_array($value) ? $value : explode(', ', $value);
        if ($field->has('pattern')) {
            $pattern = $field->get('pattern');
            $tags = array_filter($tags, static function ($item) use ($pattern) {
                return static::regex($item, $pattern);
            });
        }
        return $tags;
    }

    /**
     * Cast a value to its correct type
     *
     * @param string $value
     */
    private static function parse($value)
    {
        if (is_numeric($value)) {
            if ($value == (int) $value) {
                return (int) $value;
            }
            if ($value == (float) $value) {
                return (float) $value;
            }
        }
        return $value;
    }

    /**
     * Return whether a values matches to a regex
     *
     * @param string $value
     * @param string $regex
     *
     * @return bool
     */
    private static function regex($value, $regex)
    {
        return (bool) @preg_match('/' . $regex . '/', $value);
    }
}
