<?php

namespace Connected\Conexteo\Transport;

use Connected\Conexteo\Client\ConexteoClientInterface;
use Connected\Conexteo\Exception\ConexteoApiException;
use Connected\Conexteo\Rcs\ConexteoRCSOptions;
use Connected\Conexteo\Rcs\Content\TextContent;
use Connected\Conexteo\Rcs\SmsFallback;
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

        $options = $message->getOptions() ?? new ConexteoRCSOptions();

        if (!$options instanceof ConexteoRCSOptions) {
            throw new UnsupportedOptionsException(__CLASS__, ConexteoRCSOptions::class, $options);
        }

        $options = $options->hasContent()
            ? $this->withSubjectAsFallbackIfMissing($options, $message->getSubject())
            : $this->withSubjectAsTextContent($options, $message->getSubject());

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
            && (null === $message->getOptions() || $message->getOptions() instanceof ConexteoRCSOptions);
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

    private function withSubjectAsTextContent(ConexteoRCSOptions $options, string $subject): ConexteoRCSOptions
    {
        return (clone $options)->content(new TextContent($subject));
    }

    /**
     * Quand le contenu vient des options, le sujet du SmsMessage sert de SMS de repli, sauf si un repli est déjà défini.
     */
    private function withSubjectAsFallbackIfMissing(ConexteoRCSOptions $options, string $subject): ConexteoRCSOptions
    {
        if ($options->hasFallback() || '' === $subject) {
            return $options;
        }

        return (clone $options)->fallback(new SmsFallback($subject));
    }
}
