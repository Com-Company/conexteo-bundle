<?php

declare(strict_types=1);

namespace Connected\Conexteo\Client;

use Connected\Conexteo\Exception\ConexteoApiException;
use Connected\Conexteo\Model\MessageRcs;

interface ConexteoClientInterface
{
    /**
     * Envoie un RCS texte et retourne le `message_id` Conexteo, s'il est renvoyé par l'API.
     *
     * @throws ConexteoApiException
     */
    public function sendRcs(string $recipient, string $text): ?string;

    /**
     * Réponses reçues pour un message, telles que renvoyées par l'API
     * (`['items' => [...], 'meta' => [...]]`).
     *
     * @throws ConexteoApiException
     */
    public function getReplies(string $messageId, int $page = 1, int $limit = 50): array;

    /**
     * Même chose que getReplies(), mais les réponses sont hydratées en MessageRcs.
     *
     * @return array{messages: list<MessageRcs>, meta: array}
     *
     * @throws ConexteoApiException
     */
    public function getRepliesAsMessages(string $messageId, int $page = 1, int $limit = 50): array;
}
