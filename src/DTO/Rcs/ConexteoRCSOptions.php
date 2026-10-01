<?php

declare(strict_types=1);

namespace Connected\Conexteo\DTO\Rcs;

use Connected\Conexteo\DTO\Rcs\Content\RcsContentInterface;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;

/**
 * Contenu et options facultatives d'un RCS, à passer à `SmsMessage::options()`.
 *
 * Sans contenu, le transport envoie le sujet du SmsMessage en RCS texte.
 * Les limites (longueurs, nombre de boutons...) sont vérifiées par l'API.
 */
final class ConexteoRCSOptions implements MessageOptionsInterface
{
    /**
     * @param array<string, string> $contactVariables variables de personnalisation du destinataire (`prenom`, `commande`...)
     * @param string|null           $externalId       clé de dédoublonnage : un second envoi avec le même identifiant est rejeté par l'API (HTTP 409)
     * @param bool|null             $checkCapability  à true, seuls les contacts déjà analysés compatibles RCS le reçoivent, les autres
     *                                                reçoivent directement le SMS de repli (nécessite une liste analysée par Conexteo)
     * @param string|null           $campaignName     nom de la campagne affiché dans l'interface Conexteo
     */
    public function __construct(
        public ?RcsContentInterface $content = null,
        public ?SmsFallback $fallback = null,
        public ?ShortUrl $shortUrl = null,
        public array $contactVariables = [],
        public ?string $externalId = null,
        public ?\DateTimeImmutable $scheduleAt = null,
        public ?bool $checkCapability = null,
        public ?string $campaignName = null,
    ) {
    }

    /**
     * Payload de `POST /messages/rcs`, sans `contacts` ni `sender`.
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->content?->type(),
            'data' => $this->content?->toData(),
            'fallback' => $this->fallback?->toArray(),
            'shorturl' => $this->shortUrl?->toArray(),
            'external_id' => $this->externalId,
            'scheduleAt' => $this->scheduleAt?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'checkCapability' => $this->checkCapability,
            'name' => $this->campaignName,
        ], static fn (mixed $value): bool => null !== $value);
    }

    public function getRecipientId(): ?string
    {
        return null;
    }
}
