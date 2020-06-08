<?php namespace Semknox\Core\Services\Traits;

trait ArrayGetTrait
{
    /**
     * Get a key from an array, using dot notation.
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function arrayGet($array, $key, $default=null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return isset($array[$key])
                ? $array[$key]
                : $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
}