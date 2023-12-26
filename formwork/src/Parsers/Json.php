<?php

namespace Formwork\Parsers;

class Json extends AbstractEncoder
{
    /**
     * Default flags used to parse JSON
     */
    protected const DEFAULT_PARSE_FLAGS = JSON_THROW_ON_ERROR;

    /**
     * Default flags used to encode JSON
     */
    protected const DEFAULT_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    /**
     * Default options used to encode JSON
     */
    protected const DEFAULT_ENCODE_OPTIONS = [
        'forceObject'   => false,
        'prettyPrint'   => false,
        'escapeUnicode' => false,
    ];

    /**
     * Parse a JSON string
     *
     * @param array<string, mixed> $options
     *
     * @return array<mixed>
     */
    public static function parse(string $input, array $options = []): array
    {
        return (array) json_decode($input, true, 512, self::DEFAULT_PARSE_FLAGS);
    }

    /**
     * Encode data to JSON format
     *
     * @param array<mixed>         $data
     * @param array<string, mixed> $options
     */
    public static function encode($data, array $options = []): string
    {
        $options = [...self::DEFAULT_ENCODE_OPTIONS, ...$options];
        $flags = self::DEFAULT_ENCODE_FLAGS;
        if ($options['prettyPrint']) {
            $flags |= JSON_PRETTY_PRINT;
        }
        if (!$options['escapeUnicode']) {
            $flags |= JSON_UNESCAPED_UNICODE;
        }
        if ($data === [] || $options['forceObject']) {
            $flags |= JSON_FORCE_OBJECT;
        }
        return json_encode($data, $flags);
    }
}
