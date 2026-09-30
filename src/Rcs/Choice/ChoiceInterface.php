<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * Bouton affiché sous une carte (4 au maximum) ou un carrousel (3 au maximum).
 */
interface ChoiceInterface
{
    public const int MAX_TITLE_LENGTH = 25;

    public function toArray(): array;
}
