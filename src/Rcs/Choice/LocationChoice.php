<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * Bouton qui affiche un lieu sur une carte.
 */
final readonly class LocationChoice implements ChoiceInterface
{
    public function __construct(
        public string $title,
        public string $label,
        public float $latitude,
        public float $longitude,
    ) {
        ChoiceTitleLength::assertWithinLimit($title);
    }

    public function toArray(): array
    {
        return [
            'type' => 'location',
            'title' => $this->title,
            'label' => $this->label,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
