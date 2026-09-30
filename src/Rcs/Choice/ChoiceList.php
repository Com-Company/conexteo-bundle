<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * Boutons d'une carte ou d'un carrousel, avec leur nombre maximal.
 */
final class ChoiceList
{
    /**
     * @param list<ChoiceInterface> $choices
     */
    public static function assertNoMoreThan(array $choices, int $maxChoices, string $owner): void
    {
        if (\count($choices) > $maxChoices) {
            throw new \InvalidArgumentException(sprintf('An RCS %s accepts at most %d buttons, %d given.', $owner, $maxChoices, \count($choices)));
        }
    }

    /**
     * @param list<ChoiceInterface> $choices
     */
    public static function toArray(array $choices): array
    {
        return array_map(static fn (ChoiceInterface $choice): array => $choice->toArray(), $choices);
    }
}
