<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Choice;

/**
 * Bouton qui affiche un lieu sur une carte.
 *
 * Les coordonnées acceptent un nombre, une chaîne numérique ("48.8566")
 * ou une variable de contact ("#perso1#").
 */
final readonly class LocationChoiceDTO implements ChoiceInterface
{
    public function __construct(
        public string $title,
        public float|string $latitude,
        public float|string $longitude,
        public ?string $label = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => 'location',
            'title' => $this->title,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'label' => $this->label,
        ], static fn (float|string|null $value): bool => null !== $value);
    }
}
