<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs;

/**
 * SMS envoyé à la place du RCS quand le destinataire ne peut pas le recevoir.
 */
final readonly class SmsFallback
{
    public function __construct(
        public string $content,
        public ?string $sender = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter(['content' => $this->content, 'sender' => $this->sender], static fn (?string $value): bool => null !== $value);
    }
}
