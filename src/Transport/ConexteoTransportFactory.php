<?php

namespace Connected\Conexteo\Transport;

use Connected\Conexteo\Client\ConexteoClientFactory;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

final class ConexteoTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): ConexteoTransport
    {
        $scheme = $dsn->getScheme();

        if ('conexteo' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'conexteo', $this->getSupportedSchemes());
        }

        $conexteoClient = (new ConexteoClientFactory($this->client))->createFromDsn($dsn);
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();

        return (new ConexteoTransport($conexteoClient, $this->client, $this->dispatcher))
            ->setHost($host)
            ->setPort($dsn->getPort())
            ->setSender($dsn->getOption('sender'))
        ;
    }

    protected function getSupportedSchemes(): array
    {
        return ['conexteo'];
    }
}
