<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model\Rcs\Content;

use Connected\Conexteo\Model\Rcs\Choice\ChoiceInterface;
use Connected\Conexteo\Model\Rcs\Choice\ChoiceList;
use Connected\Conexteo\Model\Rcs\TextLengthGuard;

/**
 * Carte riche : un média public obligatoire (jpeg, jpg, png, gif, mp4, h263, m4v,
 * m4p, mpeg ou webm), un titre, une description et jusqu'à 4 boutons.
 */
final readonly class CardContent implements RcsContentInterface
{
    public const int MAX_TITLE_LENGTH = 200;
    public const int MAX_DESCRIPTION_LENGTH = 2000;
    public const int MAX_CHOICES = 4;

    /** @var list<ChoiceInterface> */
    public array $choices;

    /**
     * @param list<ChoiceInterface> $choices
     */
    public function __construct(
        public string $mediaUrl,
        public ?string $title = null,
        public ?string $description = null,
        array $choices = [],
    ) {
        if (null !== $title) {
            TextLengthGuard::assertNotLongerThan($title, self::MAX_TITLE_LENGTH, 'card title');
        }

        if (null !== $description) {
            TextLengthGuard::assertNotLongerThan($description, self::MAX_DESCRIPTION_LENGTH, 'card description');
        }

        ChoiceList::assertNoMoreThan($choices, self::MAX_CHOICES, 'card');

        $this->choices = array_values($choices);
    }

    public function type(): string
    {
        return 'card';
    }

    public function toData(): array
    {
        $data = ['media_message_url' => $this->mediaUrl];

        if (null !== $this->title) {
            $data['title'] = $this->title;
        }

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if ([] !== $this->choices) {
            $data['choices'] = ChoiceList::toArray($this->choices);
        }

        return $data;
    }
}
