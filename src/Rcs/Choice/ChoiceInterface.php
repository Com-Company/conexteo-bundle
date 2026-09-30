<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * Bouton affiché sous une carte RCS (4 au maximum par carte).
 */
interface ChoiceInterface
{
    public function toArray(): array;
}
