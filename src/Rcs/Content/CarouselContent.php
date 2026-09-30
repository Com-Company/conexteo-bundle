<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Content;

/**
 * Carrousel de 2 à 10 cartes, facturé comme un seul message.
 */
final readonly class CarouselContent implements RcsContentInterface
{
    public const int MIN_CARDS = 2;
    public const int MAX_CARDS = 10;

    /** @var list<CardContent> */
    public array $cards;

    public function __construct(CardContent ...$cards)
    {
        if (\count($cards) < self::MIN_CARDS || \count($cards) > self::MAX_CARDS) {
            throw new \InvalidArgumentException(sprintf('An RCS carousel needs between %d and %d cards, %d given.', self::MIN_CARDS, self::MAX_CARDS, \count($cards)));
        }

        $this->cards = array_values($cards);
    }

    public function type(): string
    {
        return 'carousel';
    }

    public function toData(): array
    {
        return ['cards' => array_map(static fn (CardContent $card): array => $card->toData(), $this->cards)];
    }
}
