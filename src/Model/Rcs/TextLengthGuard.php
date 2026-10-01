<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model\Rcs;

/**
 * Vérifie les longueurs maximales de `POST /messages/rcs` avant l'appel HTTP.
 */
final class TextLengthGuard
{
    public static function assertNotLongerThan(string $value, int $maxLength, string $field): void
    {
        if (mb_strlen($value) > $maxLength) {
            throw new \InvalidArgumentException(sprintf('The RCS %s exceeds %d characters (%d given).', $field, $maxLength, mb_strlen($value)));
        }
    }
}
