<?php

declare(strict_types=1);

namespace Connected\Conexteo\Client;

use Symfony\Component\Notifier\Transport\Dsn;

/**
 * Donne accès au client Conexteo d'un transport déclaré dans
 * `framework.notifier.texter_transports`, à partir de son nom.
 */
final class ConexteoClientRegistry
{
    /** @var array<string, ConexteoClientInterface> */
    private array $clients = [];

    /**
     * @param array<string, string> $texterTransportDsns nom du transport => DSN, recopiés depuis la configuration Notifier
     */
    public function __construct(
        #[\SensitiveParameter] private readonly array $texterTransportDsns,
        private readonly ConexteoClientFactory $clientFactory,
    ) {
    }

    /**
     * @param string $transportName le même nom que dans `SmsMessage::transport()`
     */
    public function get(string $transportName): ConexteoClientInterface
    {
        return $this->clients[$transportName] ??= $this->createClientForTransport($transportName);
    }

    /**
     * Un transport `null://` (Notifier désactivé, en dev par exemple) donne un client qui ne fait rien.
     */
    private function createClientForTransport(string $transportName): ConexteoClientInterface
    {
        $dsn = new Dsn($this->texterTransportDsns[$transportName] ?? throw new \InvalidArgumentException(sprintf('No texter transport named "%s" in framework.notifier.texter_transports.', $transportName)));

        return match ($dsn->getScheme()) {
            'conexteo' => $this->clientFactory->createFromDsn($dsn),
            'null' => new NullConexteoClient(),
            default => throw new \InvalidArgumentException(sprintf('The texter transport "%s" is not a Conexteo transport (scheme "%s").', $transportName, $dsn->getScheme())),
        };
    }
}
