<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Content;

use Connected\Conexteo\Rcs\Choice\ChoiceInterface;

/**
 * Carte riche : titre, description, média (JPEG, PNG, GIF ou MP4 public, pas de WebP) et boutons.
 */
final readonly class CardContent implements RcsContentInterface
{
    public const int MAX_CHOICES = 4;

    /** @var list<ChoiceInterface> */
    public array $choices;

    /**
     * @param list<ChoiceInterface> $choices
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $mediaUrl = null,
        array $choices = [],
    ) {
        if (\count($choices) > self::MAX_CHOICES) {
            throw new \InvalidArgumentException(sprintf('An RCS card accepts at most %d buttons, %d given.', self::MAX_CHOICES, \count($choices)));
        }

        $this->choices = array_values($choices);
    }

    public function type(): string
    {
        return 'card';
    }

    public function toData(): array
    {
        $data = [
            'title' => $this->title,
            'description' => $this->description,
        ];

        if (null !== $this->mediaUrl) {
            $data['media_message_url'] = $this->mediaUrl;
        }

        if ([] !== $this->choices) {
            $data['choices'] = array_map(static fn (ChoiceInterface $choice): array => $choice->toArray(), $this->choices);
        }

        return $data;
    }
}
