<?php

namespace IMEdge\Web\Grapher\Graph;

final class ShellParameter
{
    /**
     * @param array<int, string|int|bool|null>|string|int|bool|null $value
     */
    public static function renderOptional(string $name, $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if ($value === true) {
            return self::renderName($name);
        }

        if (is_array($value)) {
            $result = '';
            foreach ($value as $instance) {
                $result .= ShellParameter::renderOptional($name, $instance);
            }

            return $result;
        }

        // Important: ctype_alnum gives false for ''
        if (is_int($value) || ctype_alnum($value)) {
            return self::renderName($name) . " $value";
        }

        return self::renderName($name) . ' ' . escapeshellarg($value);
    }

    protected static function renderName(string $name): string
    {
        if (strlen($name) === 1) {
            return " -$name";
        }

        return " --$name";
    }
}
