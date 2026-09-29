<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model;

/**
 * Réponse RCS reçue d'un destinataire, telle que renvoyée par `GET /messages/replies`.
 */
final readonly class MessageRcs
{
    public function __construct(
        public string $recipient,
        public string $content,
        public \DateTimeImmutable $timestamp,
        public ?int $messageId = null,
        public ?int $replyId = null,
    ) {
    }

    public static function fromReply(array $item): self
    {
        return new self(
            recipient: $item['recipient'],
            content: $item['content'] ?? '',
            timestamp: new \DateTimeImmutable($item['receivedAt'] ?? $item['timestamp']),
            messageId: isset($item['message']['id']) ? (int) $item['message']['id'] : null,
            replyId: isset($item['id']) ? (int) $item['id'] : null,
        );
    }
}
