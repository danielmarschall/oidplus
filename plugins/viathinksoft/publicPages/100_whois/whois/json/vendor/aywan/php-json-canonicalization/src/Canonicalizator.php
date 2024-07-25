<?php

declare(strict_types=1);

namespace aywan\JsonCanonicalization;

class Canonicalizator implements JsonCanonicalizationInterface
{
    const JSON_FLAGS = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;

    public function canonicalize($data, bool $asHex = false): string
    {
        ob_start();

        $this->serialize($data);

        $result = ob_get_clean();

        return $asHex ? Utils::asHex($result) :  $result;
    }

    private function serialize($item): void
    {
        if (is_float($item)) {
            echo Utils::es6NumberFormat($item);
            return;
        }

        if (null === $item || is_scalar($item)) {
            echo json_encode($item, self::JSON_FLAGS);
            return;
        }

        if (is_array($item) && ! Utils::isAssoc($item)) {
            echo '[';
            $next = false;
            foreach ($item as $element) {
                if ($next) {
                    echo ',';
                }
                $next = true;
                $this->serialize($element);
            }
            echo ']';
            return;
        }

        if (is_object($item)) {
            $item = (array)$item;
        }

        uksort($item, function (string $a, string $b) {
            $a = mb_convert_encoding($a, 'UTF-16BE');
            $b = mb_convert_encoding($b, 'UTF-16BE');
            return strcmp($a, $b);
        });

        echo '{';
        $next = false;
        foreach ($item as $key => $value) {
            if ($next) {
                echo ',';
            }
            $next = true;
            $outKey = json_encode((string)$key, self::JSON_FLAGS);
            echo $outKey, ':', $this->serialize($value);
        }
        echo '}';
    }
}