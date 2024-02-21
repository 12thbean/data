<?php

namespace Zendrop\Data;


use Illuminate\Support\Str;

trait ToArrayTrait
{
    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(ToArrayCase $toCase = ToArrayCase::Snake): array
    {
        $array = (array) $this;

        return $this->toArrayR($array, $toCase);
    }

    protected function toArrayR(mixed $value, ToArrayCase $toCase): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if ($item instanceof Skippable) {
                    continue;
                }

                $result[$this->convertToCase($key, $toCase)] = static::toArrayR($item, $toCase);
            }

            return $result;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_object($value)) {
            if ($value instanceof ToArrayInterface) {
                return $value->toArray($toCase);
            }

            if (method_exists($value, 'toArray')) {
                $result = $value->toArray();
            } else {
                $result = (array) $value;
            }

            return $this->convertArrayKeysToCase($result, $toCase);
        }

        return $value;
    }

    protected function convertArrayKeysToCase(array $array, ToArrayCase $toCase): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[$this->convertToCase($key, $toCase)] = $value;
        }

        return $result;
    }

    protected function convertToCase(string|int $key, ToArrayCase $toCase): int|string
    {
        if (is_int($key)) {
            return $key;
        }

        return match($toCase) {
            ToArrayCase::Camel => Str::camel($key),
            ToArrayCase::Snake => Str::snake($key),
            ToArrayCase::Kebab => Str::kebab($key),
        };
    }
}
