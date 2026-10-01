<?php

namespace Connected\Conexteo\Transport;

use Connected\Conexteo\Client\ConexteoClientInterface;
use Connected\Conexteo\Exception\ConexteoApiException;
use Connected\Conexteo\DTO\Rcs\ConexteoRCSOptionsDTO;
use Connected\Conexteo\DTO\Rcs\Content\TextContentDTO;
use Connected\Conexteo\DTO\Rcs\SmsFallbackDTO;
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

        $options = null === $options->content
            ? $this->withSubjectAsTextContent($options, $message->getSubject())
            : $this->withSubjectAsFallbackIfMissing($options, $message->getSubject());

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
     * Travaille sur une copie : les options du SmsMessage appelant ne sont pas modifiées.
     */
    private function withSubjectAsTextContent(ConexteoRCSOptionsDTO $options, string $subject): ConexteoRCSOptionsDTO
    {
        $options = clone $options;
        $options->content = new TextContentDTO($subject);

        return $options;
    }

    /**
     * Quand le contenu vient des options, le sujet du SmsMessage sert de SMS de repli, sauf si un repli est déjà défini.
     */
    private function withSubjectAsFallbackIfMissing(ConexteoRCSOptionsDTO $options, string $subject): ConexteoRCSOptionsDTO
    {
        if (null !== $options->fallback || '' === $subject) {
            return $options;
        }

        $options = clone $options;
        $options->fallback = new SmsFallbackDTO($subject);

        return $options;
    }
}
