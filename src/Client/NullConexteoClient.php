<?php

declare(strict_types=1);

namespace Connected\Conexteo\Client;

/**
 * Client qui n'appelle pas l'API, utilisé quand le transport Notifier est `null://`.
 */
final class NullConexteoClient implements ConexteoClientInterface
{
    public function sendRcs(string $recipient, string $text): ?string
    {
        return null;
    }

    public function getReplies(string $messageId, int $page = 1, int $limit = 50): array
    {
        return ['items' => [], 'meta' => []];
    }

    public function getRepliesAsMessages(string $messageId, int $page = 1, int $limit = 50): array
    {
        return ['messages' => [], 'meta' => []];
    }
}
