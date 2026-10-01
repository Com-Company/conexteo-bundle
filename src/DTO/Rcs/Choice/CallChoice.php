<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Choice;

/**
 * Bouton qui lance un appel téléphonique.
 */
final readonly class CallChoice implements ChoiceInterface
{
    public function __construct(
        public string $title,
        public string $phoneNumber,
    ) {
    }

    public function toArray(): array
    {
        return ['type' => 'call', 'title' => $this->title, 'phoneNumber' => $this->phoneNumber];
    }
}
