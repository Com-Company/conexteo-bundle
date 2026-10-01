<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Content;

use Connected\Conexteo\DTO\Rcs\Choice\ChoiceInterface;

/**
 * Carte riche : un média public obligatoire (jpeg, jpg, png, gif, mp4, h263, m4v,
 * m4p, mpeg ou webm), un titre, une description et des boutons facultatifs.
 */
final readonly class CardContentDTO implements RcsContentInterface
{
    /**
     * @param list<ChoiceInterface> $choices
     */
    public function __construct(
        public string $mediaUrl,
        public ?string $title = null,
        public ?string $description = null,
        public array $choices = [],
    ) {
    }

    public function type(): string
    {
        return 'card';
    }

    public function toData(): array
    {
        return array_filter([
            'media_message_url' => $this->mediaUrl,
            'title' => $this->title,
            'description' => $this->description,
            'choices' => array_map(static fn (ChoiceInterface $choice): array => $choice->toArray(), $this->choices),
        ], static fn (mixed $value): bool => null !== $value && [] !== $value);
    }
}
