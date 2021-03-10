<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Subscriber;

use vavo\SwoolSocket\Service\ConnectionStorage;
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

			if ($message->getBody() !== '') {
				return; // Empty body, nothing to send
			}

			foreach ($server->connections as $fd) {
				$connection = $this->connectionStorage->getConnectionById($fd);

				if ($connection === null) {
					return; // Connection does not exist
				}

				// Send message to the recipient
				if ($connection->getTopicId() === $message->getTopicId()) {
					$server->push($fd, $message->getBody());
				}
			}
		}
	}

}
