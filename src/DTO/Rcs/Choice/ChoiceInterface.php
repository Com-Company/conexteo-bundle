<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Choice;

/**
 * Bouton affiché sous une carte ou un carrousel.
 */
interface ChoiceInterface
{
    public function toArray(): array;
}
