<?php

namespace Formwork\Parsers;

use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Yaml extends AbstractEncoder
{
    /**
     * Parse a YAML string
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function parse(string $input, array $options = []): array
    {
        return (array) SymfonyYaml::parse($input);
    }

    /**
     * Encode data to YAML format
     *
     * @param array<string, mixed> $options
     */
    public static function encode(mixed $data, array $options = []): string
    {
        if (empty($data)) {
            return '';
        }
        return SymfonyYaml::dump($data, inline: 4);
    }
}
