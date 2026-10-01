<?php

declare(strict_types=1);

namespace Connected\Conexteo\Model\Rcs;

use Connected\Conexteo\Model\Rcs\Content\RcsContentInterface;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;

/**
 * Contenu et options facultatives d'un RCS, à passer à `SmsMessage::options()`.
 *
 * Sans contenu, le transport envoie le sujet du SmsMessage en RCS texte.
 */
final class ConexteoRCSOptions implements MessageOptionsInterface
{
    private ?RcsContentInterface $content = null;

    private ?SmsFallback $fallback = null;

    private ?ShortUrl $shortUrl = null;

    /** @var array<string, string> */
    private array $contactVariables = [];

    /** @var array<string, string|bool> */
    private array $options = [];

    public function content(RcsContentInterface $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function fallback(SmsFallback $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    public function shortUrl(ShortUrl $shortUrl): static
    {
        $this->shortUrl = $shortUrl;

        return $this;
    }

    /**
     * Variables de personnalisation du destinataire (`prenom`, `commande`...).
     *
     * @param array<string, string> $contactVariables
     */
    public function contactVariables(array $contactVariables): static
    {
        $this->contactVariables = $contactVariables;

        return $this;
    }

    /**
     * Clé de dédoublonnage : un second envoi avec le même identifiant est rejeté par l'API (HTTP 409).
     */
    public function externalId(string $externalId): static
    {
        $this->options['external_id'] = $externalId;

        return $this;
    }

    public function scheduleAt(\DateTimeImmutable $scheduleAt): static
    {
        $this->options['scheduleAt'] = $scheduleAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        return $this;
    }

    /**
     * À true, seuls les contacts déjà analysés compatibles RCS le reçoivent, les autres reçoivent
     * directement le SMS de repli (nécessite une liste analysée par Conexteo). À false, le RCS
     * part vers tous et le repli SMS se fait au retour de l'opérateur.
     */
    public function checkCapability(bool $checkCapability = true): static
    {
        $this->options['checkCapability'] = $checkCapability;

        return $this;
    }

    /**
     * Nom de la campagne affiché dans l'interface Conexteo.
     */
    public function campaignName(string $campaignName): static
    {
        $this->options['name'] = $campaignName;

        return $this;
    }

    public function hasContent(): bool
    {
        return null !== $this->content;
    }

    public function hasFallback(): bool
    {
        return null !== $this->fallback;
    }

    /**
     * @return array<string, string>
     */
    public function getContactVariables(): array
    {
        return $this->contactVariables;
    }

    /**
     * Payload de `POST /messages/rcs`, sans `contacts` ni `sender`.
     */
    public function toArray(): array
    {
        $payload = $this->options;

        if (null !== $this->content) {
            $payload['type'] = $this->content->type();
            $payload['data'] = $this->content->toData();
        }

        if (null !== $this->fallback) {
            $payload['fallback'] = $this->fallback->toArray();
        }

        if (null !== $this->shortUrl) {
            $payload['shorturl'] = $this->shortUrl->toArray();
        }

        return $payload;
    }

    public function getRecipientId(): ?string
    {
        return null;
    }
}
