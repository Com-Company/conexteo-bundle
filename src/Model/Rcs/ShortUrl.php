<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model\Rcs;

/**
 * Lien raccourci par Conexteo.
 */
final readonly class ShortUrl
{
    public function __construct(
        public string $url,
        public ShortUrlMode $mode,
    ) {
    }

    public function toArray(): array
    {
        return ['mode' => $this->mode->value, 'url' => $this->url];
    }
}
