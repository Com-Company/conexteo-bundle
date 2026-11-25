<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Content;

use Connected\Conexteo\DTO\Rcs\Choice\ChoiceInterface;

/**
 * Carrousel de cartes, facturé comme un seul message, avec des boutons communs
 * facultatifs sous le carrousel.
 */
final readonly class CarouselContentDTO implements RcsContentInterface
{
    /**
     * @param list<CardContentDTO>     $cards
     * @param list<ChoiceInterface> $choices
     */
    public function __construct(
        public array $cards,
        public array $choices = [],
    ) {
    }

    public function type(): string
    {
        return 'carousel';
    }

    public function toData(): array
    {
        $data = ['cards' => array_map(static fn (CardContentDTO $card): array => $card->toData(), $this->cards)];

        if ([] !== $this->choices) {
            $data['choices'] = array_map(static fn (ChoiceInterface $choice): array => $choice->toArray(), $this->choices);
        }

        return $data;
    }
}
