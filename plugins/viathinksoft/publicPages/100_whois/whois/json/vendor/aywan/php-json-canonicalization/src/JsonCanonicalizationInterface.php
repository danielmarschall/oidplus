<?php

declare(strict_types=1);

namespace aywan\JsonCanonicalization;

interface JsonCanonicalizationInterface
{
    public function canonicalize($data, bool $asHex = false): string;
}