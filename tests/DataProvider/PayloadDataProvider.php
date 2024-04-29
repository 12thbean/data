<?php

namespace Zendrop\Data\Tests\DataProvider;

use Illuminate\Support\Str;

class PayloadDataProvider
{
    /**
     * Generate an array with the option to use snake case for keys.
     *
     * @param bool $useSnakeCase Whether to convert keys to snake case.
     * @return array The generated array.
     */
    public static function getArray(bool $useSnakeCase = false): array
    {
        $array = [
            'userName' => 'Dima',
            'age' => '29',
            'testEnum' => 'red',
            'weight' => 110.2,
            'nums' => [1, '2', 3.23, 4],
            'hobby' => [
                'name' => 'dancing',
            ],
            'hobbies' => [
                [
                    'name' => 'mbx',
                ],
                [
                    'name' => 'singing',
                ],
            ],
            'colors' => ['white', 'red', 'green'],
            'isCool' => 'On',
            'motorbike' => 'Kawasaki Ninja 400'
        ];

        return $useSnakeCase ? self::convertKeysToSnakeCase($array) : $array;
    }

    /**
     * Generate a strictly typed array with the option to use snake case for keys.
     *
     * @param bool $useSnakeCase Whether to convert keys to snake case.
     * @return array The generated array.
     */
    public static function getStrictValuesArray(bool $useSnakeCase = false): array
    {
        $array = [
            'userName' => 'Dima',
            'age' => 29,
            'testEnum' => 'red',
            'weight' => 110.2,
            'nums' => ['1', '2', '3.23', '4'],
            'hobby' => [
                'name' => 'dancing',
            ],
            'hobbies' => [
                [
                    'name' => 'mbx',
                ],
                [
                    'name' => 'singing',
                ],
            ],
            'colors' => ['white', 'red', 'green'],
            'isCool' => true,
            'motorbike' => 'Kawasaki Ninja 400'
        ];

        return $useSnakeCase ? self::convertKeysToSnakeCase($array) : $array;
    }

    /**
     * Convert all keys in an array to snake case.
     *
     * @param array $array The array to convert.
     * @return array The array with snake case keys.
     */
    private static function convertKeysToSnakeCase(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $transformedKey = is_int($key) ? $key : Str::snake($key);
            if (is_array($value)) {
                $value = self::convertKeysToSnakeCase($value);
            }
            $result[$transformedKey] = $value;
        }
        return $result;
    }
}
