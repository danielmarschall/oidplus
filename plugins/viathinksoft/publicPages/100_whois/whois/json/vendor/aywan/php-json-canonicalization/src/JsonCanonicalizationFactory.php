<?php

declare(strict_types=1);

namespace aywan\JsonCanonicalization;

class JsonCanonicalizationFactory
{
    public static function getInstance(): JsonCanonicalizationInterface
    {
        return new Canonicalizator();
    }
}