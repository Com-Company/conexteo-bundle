<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs\Choice;

/**
 * Bouton qui ouvre un lien.
 */
final readonly class UrlChoice implements ChoiceInterface
{
    public function __construct(
        public string $title,
        public string $url,
    ) {
    }

    public function toArray(): array
    {
        return ['type' => 'url', 'title' => $this->title, 'url' => $this->url];
    }
}
