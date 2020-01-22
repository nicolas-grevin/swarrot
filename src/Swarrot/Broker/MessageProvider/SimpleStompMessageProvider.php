<?php

namespace Swarrot\Broker\MessageProvider;

use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\SimpleStomp;
use Stomp\Transport\Message as StompMessage;
use Swarrot\Broker\Message;

class SimpleStompMessageProvider implements MessageProviderInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var SimpleStomp
     */
    private $stomp;

    /**
     * @var string
     */
    private $destination;

    /**
     * @param string $destination
     * @param null   $subscriptionId
     * @param string $ack
     * @param null   $selector
     */
    public function __construct(
        Client $client,
        $destination,
        $subscriptionId = null,
        $ack = 'client',
        $selector = null,
        array $header = []
    ) {
        @trigger_error(sprintf('"%s" have been deprecated since Swarrot 3.6', __CLASS__), E_USER_DEPRECATED);

        $this->client = $client;
        $this->destination = $destination;

        $this->stomp = new SimpleStomp($client);
        $this->stomp->subscribe($destination, $subscriptionId, $ack, $selector, $header);
    }

    public function get()
    {
        if ($frame = $this->stomp->read()) {
            return new Message($frame->getBody(), $frame->getHeaders());
        }

        return null;
    }

    public function ack(Message $message)
    {
        $this->stomp->ack(new StompMessage($message->getBody(), $message->getProperties()));
    }

    /**
     * @param bool $requeue
     *
     * @throws StompException
     */
    public function nack(Message $message, $requeue = false)
    {
        $protocol = $this->client->getProtocol();
        if (null === $protocol) {
            throw new StompException('Stomp protocol is require to NACK Frames.');
        }

        $this->client->sendFrame(
            $protocol->getNackFrame(
                new StompMessage($message->getBody(), $message->getProperties()),
                null,
                $requeue
            ),
            false
        );
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->destination;
    }
}
