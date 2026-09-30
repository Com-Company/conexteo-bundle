<?php

declare(strict_types=1);

namespace Connected\Conexteo\Rcs;

/**
 * SMS envoyé à la place du RCS quand le destinataire ne peut pas le recevoir.
 */
final readonly class SmsFallback
{
    public const int MAX_SENDER_LENGTH = 11;

    public function __construct(
        public string $content,
        public ?string $sender = null,
    ) {
        if (null !== $sender) {
            TextLengthGuard::assertNotLongerThan($sender, self::MAX_SENDER_LENGTH, 'fallback sender');
        }
    }

    public function toArray(): array
    {
        $fallback = ['content' => $this->content];

        if (null !== $this->sender) {
            $fallback['sender'] = $this->sender;
        }

        return $fallback;
    }
}
