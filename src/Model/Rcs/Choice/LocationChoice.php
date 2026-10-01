<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model\Rcs\Choice;

use Connected\Conexteo\Model\Rcs\TextLengthGuard;

/**
 * Bouton qui affiche un lieu sur une carte.
 *
 * Les coordonnées acceptent un nombre, une chaîne numérique ("48.8566")
 * ou une variable de contact ("#perso1#").
 */
final readonly class LocationChoice implements ChoiceInterface
{
    public const int MAX_LABEL_LENGTH = 1000;

    public function __construct(
        public string $title,
        public float|string $latitude,
        public float|string $longitude,
        public ?string $label = null,
    ) {
        TextLengthGuard::assertNotLongerThan($title, self::MAX_TITLE_LENGTH, 'button title');

        if (null !== $label) {
            TextLengthGuard::assertNotLongerThan($label, self::MAX_LABEL_LENGTH, 'location label');
        }
    }

    public function toArray(): array
    {
        $choice = [
            'type' => 'location',
            'title' => $this->title,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];

        if (null !== $this->label) {
            $choice['label'] = $this->label;
        }

        return $choice;
    }
}
