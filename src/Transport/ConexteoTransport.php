<?php

namespace Connected\Conexteo\Transport;

use Connected\Conexteo\Model\MessageRcs;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ConexteoTransport extends AbstractTransport
{
    protected const string HOST = 'api.conexteo.com';

    private ?string $sender = null;

    public function __construct(private readonly string $appId, private readonly string $apiKey, ?HttpClientInterface $client = null, ?EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($client, $dispatcher);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $content = [
            'type' => 'text',
            'data' => ['text' => $message->getSubject()],
            'contacts' => [['recipient' => $message->getPhone()]],
        ];

        if ($this->sender !== null) {
            $content['sender'] = $this->sender;
        }

        $response = $this->client->request('POST', $this->url('/messages/rcs'), [
            'headers' => $this->headers('application/json'),
            'json' => $content,
        ]);

        $data = $this->decode($response, 'Unable to send the RCS message');

        $sentMessage = new SentMessage($message, (string) $this);

        if (isset($data['message_id'])) {
            $sentMessage->setMessageId((string) $data['message_id']);
        }

        return $sentMessage;
    }

    /**
     * Réponses reçues pour un message, telles que renvoyées par l'API
     * (`['items' => [...], 'meta' => [...]]`).
     */
    public function getReplies(string $messageId, int $page = 1, int $limit = 50): array
    {
        $response = $this->client->request('GET', $this->url('/messages/replies'), [
            'headers' => $this->headers(),
            'query' => [
                'message_like' => $messageId,
                'page' => $page,
                'limit' => $limit,
            ],
        ]);

        return $this->decode($response, 'Unable to fetch the RCS replies');
    }

    /**
     * Même chose que getReplies(), mais les réponses sont hydratées en MessageRcs.
     *
     * @return array{messages: list<MessageRcs>, meta: array}
     */
    public function getRepliesAsMessages(string $messageId, int $page = 1, int $limit = 50): array
    {
        $data = $this->getReplies($messageId, $page, $limit);

        return [
            'messages' => array_map(MessageRcs::fromReply(...), $data['items'] ?? []),
            'meta' => $data['meta'] ?? [],
        ];
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    public function __toString(): string
    {
        if (null !== $this->sender) {
            return sprintf('conexteo://%s?sender=%s', $this->getEndpoint(), $this->sender);
        }

        return sprintf('conexteo://%s', $this->getEndpoint());
    }

    public function setSender(?string $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    private function url(string $path): string
    {
        return sprintf('https://%s%s', $this->getEndpoint(), $path);
    }

    private function headers(?string $contentType = null): array
    {
        $headers = [
            'X-APP-ID' => $this->appId,
            'X-API-KEY' => $this->apiKey,
            'Accept' => 'application/json',
        ];

        if (null !== $contentType) {
            $headers['Content-Type'] = $contentType;
        }

        return $headers;
    }

    /**
     * @throws TransportException si le serveur est injoignable, renvoie une erreur
     *                            ou un corps qui n'est pas exploitable en JSON
     */
    private function decode(ResponseInterface $response, string $errorMessage): array
    {
        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote Conexteo server.', $response, 0, $e);
        }

        try {
            $data = $response->toArray(false);
        } catch (DecodingExceptionInterface|TransportExceptionInterface $e) {
            if ($statusCode < 400) {
                throw new TransportException('Unexpected response from the remote Conexteo server.', $response, 0, $e);
            }

            $data = [];
        }

        if ($statusCode >= 400) {
            throw new TransportException(sprintf('%s: %s.', $errorMessage, json_encode($data['message'] ?? $data, JSON_THROW_ON_ERROR)), $response);
        }

        return $data;
    }
}
