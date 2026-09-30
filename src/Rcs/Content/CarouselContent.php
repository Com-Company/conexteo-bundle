<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Content;

use Connected\Conexteo\Rcs\Choice\ChoiceInterface;
use Connected\Conexteo\Rcs\Choice\ChoiceList;

/**
 * Carrousel de 1 à 10 cartes, facturé comme un seul message, avec jusqu'à
 * 3 boutons communs sous le carrousel.
 */
final readonly class CarouselContent implements RcsContentInterface
{
    public const int MAX_CARDS = 10;
    public const int MAX_CHOICES = 3;

    /** @var list<CardContent> */
    public array $cards;

    /** @var list<ChoiceInterface> */
    public array $choices;

    /**
     * @param list<CardContent>     $cards
     * @param list<ChoiceInterface> $choices
     */
    public function __construct(array $cards, array $choices = [])
    {
        if ([] === $cards || \count($cards) > self::MAX_CARDS) {
            throw new \InvalidArgumentException(sprintf('An RCS carousel needs between 1 and %d cards, %d given.', self::MAX_CARDS, \count($cards)));
        }

        ChoiceList::assertNoMoreThan($choices, self::MAX_CHOICES, 'carousel');

        $this->cards = array_values($cards);
        $this->choices = array_values($choices);
    }

    public function type(): string
    {
        return 'carousel';
    }

    public function toData(): array
    {
        $data = ['cards' => array_map(static fn (CardContent $card): array => $card->toData(), $this->cards)];

        if ([] !== $this->choices) {
            $data['choices'] = ChoiceList::toArray($this->choices);
        }

        return $data;
    }
}
