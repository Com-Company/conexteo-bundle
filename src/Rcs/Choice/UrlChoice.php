<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

/**
 * Bouton qui ouvre un lien.
 */
final readonly class UrlChoice implements ChoiceInterface
{
    public function __construct(
        public string $title,
        public string $url,
    ) {
        ChoiceTitleLength::assertWithinLimit($title);
    }

    public function toArray(): array
    {
        return ['type' => 'url', 'title' => $this->title, 'url' => $this->url];
    }
}
