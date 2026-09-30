<?php

declare(strict_types=1);

namespace Connected\Conexteo\Client;

use Connected\Conexteo\Exception\ConexteoApiException;
use Connected\Conexteo\Model\MessageRcs;
use Connected\Conexteo\Rcs\ConexteoRCSOptions;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ConexteoClient implements ConexteoClientInterface
{
    public const string DEFAULT_ENDPOINT = 'api.conexteo.com';

    private readonly HttpClientInterface $httpClient;

    public function __construct(
        string $appId,
        string $apiKey,
        private readonly ?string $sender,
        HttpClientInterface $httpClient,
        string $endpoint = self::DEFAULT_ENDPOINT,
    ) {
        $this->httpClient = $httpClient->withOptions([
            'base_uri' => sprintf('https://%s', $endpoint),
            'headers' => [
                'X-APP-ID' => $appId,
                'X-API-KEY' => $apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function sendRcs(string $recipient, ConexteoRCSOptions $options, ?string $sender = null): ?string
    {
        $payload = $this->buildRcsPayload($recipient, $options, $sender ?? $this->sender);

        $response = $this->httpClient->request('POST', '/messages/rcs', ['json' => $payload]);

        $data = $this->decodeResponseOrThrow($response, 'Unable to send the RCS message');

        return isset($data['message_id']) ? (string) $data['message_id'] : null;
    }

    public function getReplies(string $messageId, int $page = 1, int $limit = 50): array
    {
        $response = $this->httpClient->request('GET', '/messages/replies', [
            'query' => [
                'message_like' => $messageId,
                'page' => $page,
                'limit' => $limit,
            ],
        ]);

        return $this->decodeResponseOrThrow($response, 'Unable to fetch the RCS replies');
    }

    public function getRepliesAsMessages(string $messageId, int $page = 1, int $limit = 50): array
    {
        $data = $this->getReplies($messageId, $page, $limit);

        return [
            'messages' => array_map(MessageRcs::fromReply(...), $data['items'] ?? []),
            'meta' => $data['meta'] ?? [],
        ];
    }

    private function buildRcsPayload(string $recipient, ConexteoRCSOptions $options, ?string $sender): array
    {
        if (!$options->hasContent()) {
            throw new \InvalidArgumentException('The RCS options have no content: call ConexteoRCSOptions::content().');
        }

        $payload = $options->toArray();
        $payload['contacts'] = [['recipient' => $recipient] + $options->getContactVariables()];

        if (null !== $sender) {
            $payload['sender'] = $sender;
        }

        return $payload;
    }

    /**
     * @throws ConexteoApiException si le serveur est injoignable, renvoie une erreur
     *                              ou un corps qui n'est pas exploitable en JSON
     */
    private function decodeResponseOrThrow(ResponseInterface $response, string $errorMessage): array
    {
        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new ConexteoApiException('Could not reach the remote Conexteo server.', $response, $e);
        }

        try {
            $data = $response->toArray(false);
        } catch (DecodingExceptionInterface|TransportExceptionInterface $e) {
            if ($statusCode < 400) {
                throw new ConexteoApiException('Unexpected response from the remote Conexteo server.', $response, $e);
            }

            $data = [];
        }

        if ($statusCode >= 400) {
            throw new ConexteoApiException(sprintf('%s: %s.', $errorMessage, json_encode($data['message'] ?? $data, JSON_THROW_ON_ERROR)), $response);
        }

        return $data;
    }
}
