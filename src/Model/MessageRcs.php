<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model;

/**
 * Représentation normalisée d'un message RCS, qu'il ait été envoyé par
 * l'application (`outbound`) ou reçu en réponse par un destinataire (`inbound`).
 */
final readonly class MessageRcs
{
    public const string DIRECTION_OUTBOUND = 'outbound';
    public const string DIRECTION_INBOUND = 'inbound';

    public function __construct(
        public string $direction,
        public string $recipient,
        public string $content,
        public \DateTimeImmutable $timestamp,
        public ?int $messageId = null,
        public ?int $replyId = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'direction' => $this->direction,
            'recipient' => $this->recipient,
            'content' => $this->content,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'message_id' => $this->messageId,
            'reply_id' => $this->replyId,
        ];
    }

    /**
     * Construit le message à partir d'un envoi réalisé via le transport.
     */
    public static function fromSent(array $data): self
    {
        return new self(
            direction: self::DIRECTION_OUTBOUND,
            recipient: $data['recipient'],
            content: $data['message'],
            timestamp: new \DateTimeImmutable($data['sent_at'] ?? 'now'),
            messageId: isset($data['message_id']) ? (int) $data['message_id'] : null,
        );
    }

    /**
     * Construit le message à partir d'une entrée renvoyée par `GET /messages/replies`.
     */
    public static function fromReply(array $item): self
    {
        return new self(
            direction: self::DIRECTION_INBOUND,
            recipient: $item['recipient'],
            content: $item['content'] ?? '',
            timestamp: new \DateTimeImmutable($item['receivedAt'] ?? $item['timestamp']),
            messageId: isset($item['message']['id']) ? (int) $item['message']['id'] : null,
            replyId: isset($item['id']) ? (int) $item['id'] : null,
        );
    }
}
