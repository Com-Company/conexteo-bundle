<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs\Choice;

use Connected\Conexteo\Rcs\TextLengthGuard;

/**
 * Bouton qui ouvre un lien.
 */
final readonly class UrlChoice implements ChoiceInterface
{
    public const int MAX_URL_LENGTH = 2048;

    public function __construct(
        public string $title,
        public string $url,
    ) {
        TextLengthGuard::assertNotLongerThan($title, self::MAX_TITLE_LENGTH, 'button title');
        TextLengthGuard::assertNotLongerThan($url, self::MAX_URL_LENGTH, 'button url');
    }

    public function toArray(): array
    {
        return ['type' => 'url', 'title' => $this->title, 'url' => $this->url];
    }
}
