<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * L'API rejette les boutons dont le libellé dépasse 25 caractères.
 */
final class ChoiceTitleLength
{
    public const int MAX_LENGTH = 25;

    public static function assertWithinLimit(string $title): void
    {
        if (mb_strlen($title) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('The RCS button title "%s" exceeds %d characters.', $title, self::MAX_LENGTH));
        }
    }
}
