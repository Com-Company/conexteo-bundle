<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * Bouton qui lance un appel téléphonique.
 */
final readonly class CallChoice implements ChoiceInterface
{
    public function __construct(
        public string $title,
        public string $phoneNumber,
    ) {
        ChoiceTitleLength::assertWithinLimit($title);
    }

    public function toArray(): array
    {
        return ['type' => 'call', 'title' => $this->title, 'phoneNumber' => $this->phoneNumber];
    }
}
