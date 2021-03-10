<?php declare(strict_types = 1);

namespace App\Extensions\Websocket\Subscriber;

use vavo\SwoolSocket\Service\ConnectionStorage;
use vavo\SwoolSocket\Subscriber\IChannelSubscriber;
use Swoole\WebSocket\Server as SwooleServer;

abstract class ChannelSubscriber implements IChannelSubscriber
{

	/**
	 * @var ConnectionStorage
	 */
	private ConnectionStorage $connectionStorage;

	public function __construct(ConnectionStorage $connectionStorage)
	{
		$this->connectionStorage = $connectionStorage;
	}

	public function subscribe(SwooleServer $server, string $serializedMessage, string $channel): void
	{
		if ($serializedMessage !== '' && $this->getChannelName() === $channel) {
			$message = $this->createMessage($channel, $serializedMessage);

			if ($message->getBody() === '') {
				return; // Empty body, nothing to send
			}

			foreach ($this->connectionStorage->getWorkerConnections() as $fd => $hash) {
				$connection = $this->connectionStorage->getConnectionByHash($hash);

				if ($connection === null) {
					return; // Connection does not exist
				}

				// Send message to the recipient
				if ($server->connections->offsetExists($fd) && $connection->getTopicId() === $message->getTopicId()) {
					$server->push($fd, $message->getBody());
				}
			}
		}
	}

}
