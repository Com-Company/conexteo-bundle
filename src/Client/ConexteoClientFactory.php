<?php

declare(strict_types=1);

namespace Connected\Conexteo\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Construit un client à partir d'un DSN `conexteo://APP_ID:API_KEY@default?sender=SENDER`,
 * le même que celui déclaré dans `framework.notifier.texter_transports`.
 */
final class ConexteoClientFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function createFromDsn(Dsn $dsn): ConexteoClient
    {
        return new ConexteoClient(
            $dsn->getUser() ?? throw new IncompleteDsnException('User is not set.', $dsn->getScheme().'://'.$dsn->getHost()),
            $dsn->getPassword() ?? throw new IncompleteDsnException('Password is not set.', $dsn->getOriginalDsn()),
            $dsn->getOption('sender'),
            $this->httpClient,
            $this->buildEndpoint($dsn),
            $this->logger,
        );
    }

    private function buildEndpoint(Dsn $dsn): string
    {
        $host = 'default' === $dsn->getHost() ? ConexteoClient::DEFAULT_ENDPOINT : $dsn->getHost();

        return null === $dsn->getPort() ? $host : sprintf('%s:%d', $host, $dsn->getPort());
    }
}
