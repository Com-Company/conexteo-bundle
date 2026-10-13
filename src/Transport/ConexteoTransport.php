<?php

namespace Connected\Conexteo\Transport;

use Connected\Conexteo\Client\ConexteoClientInterface;
use Connected\Conexteo\DTO\Rcs\ConexteoRCSOptionsDTO;
use Connected\Conexteo\DTO\Rcs\Content\TextContentDTO;
use Connected\Conexteo\DTO\Rcs\SmsFallbackDTO;
use Connected\Conexteo\Exception\ConexteoApiException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Exception\UnsupportedOptionsException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adapte le contrat Notifier au client Conexteo : les appels HTTP sont
 * dans ConexteoClient.
 */
final class ConexteoTransport extends AbstractTransport
{
    protected const string HOST = 'api.conexteo.com';

    private ?string $sender = null;

    public function __construct(private readonly ConexteoClientInterface $conexteoClient, ?HttpClientInterface $client = null, ?EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($client, $dispatcher);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $options = $message->getOptions() ?? new ConexteoRCSOptionsDTO();

        if (!$options instanceof ConexteoRCSOptionsDTO) {
            throw new UnsupportedOptionsException(__CLASS__, ConexteoRCSOptionsDTO::class, $options);
        }

        $options = $this->withSubjectDefaults($options, $message->getSubject());

        $sender = '' !== $message->getFrom() ? $message->getFrom() : null;

        try {
            $messageId = $this->conexteoClient->sendRcs($message->getPhone(), $options, $sender);
        } catch (ConexteoApiException $e) {
            throw new TransportException($e->getMessage(), $e->response, 0, $e);
        }

        $sentMessage = new SentMessage($message, (string) $this);

        if (null !== $messageId) {
            $sentMessage->setMessageId($messageId);
        }

        return $sentMessage;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage
            && (null === $message->getOptions() || $message->getOptions() instanceof ConexteoRCSOptionsDTO);
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

    /**
     * Le sujet du SmsMessage sert de contenu RCS texte quand les options n'en définissent pas,
     * et de SMS de repli quand aucun repli n'est défini : sans repli, les mobiles non compatibles
     * RCS ne recevraient rien.
     *
     * Travaille sur une copie : les options du SmsMessage appelant ne sont pas modifiées.
     */
    private function withSubjectDefaults(ConexteoRCSOptionsDTO $options, string $subject): ConexteoRCSOptionsDTO
    {
        $needsContent = null === $options->content;
        $needsFallback = null === $options->fallback && '' !== $subject;

        if (!$needsContent && !$needsFallback) {
            return $options;
        }

        $options = clone $options;

        if ($needsContent) {
            $options->content = new TextContentDTO($subject);
        }

        if ($needsFallback) {
            $options->fallback = new SmsFallbackDTO($subject);
        }

        return $options;
    }
}