<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Subscriber;

use vavo\SwoolSocket\Service\ConnectionStorage;
use Nette\Utils\Json;
use Swoole\WebSocket\Server as SwooleServer;

abstract class ChannelSubscriber implements IChannelSubscriber
{
	/** @var ConnectionStorage */
	private ConnectionStorage $connectionStorage;

	public function __construct(ConnectionStorage $connectionStorage)
	{
		$this->connectionStorage = $connectionStorage;
	}

	public function subscribe(SwooleServer $server, string $serializedMessage, string $channel): void
	{
		echo sprintf("Processing the message in %s\n", $channel);

		if ($serializedMessage !== '' && $this->getChannelName() === $channel) {
			$message = $this->createMessage($channel, $serializedMessage);

			if ($message->getParams() === []) {
				echo "The message has empty body.\n";
				return; // Empty body, nothing to send
			}

			foreach ($this->connectionStorage->getWorkerConnections() as $fd => $hash) {
				$connection = $this->connectionStorage->getConnectionByHash($hash);

				if ($connection === null) {
					echo "The message receiver not found.\n";
					return; // Connection does not exist
				}

				// Send message to the recipient
				if ($server->connections->offsetExists($fd) && $connection->getTopicId() === $message->getTopicId()) {
					$server->push($fd, Json::encode(['message' => $message->getParams()]));
				}
			}
		}
	}
}
