<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use vavo\SwoolSocket\Subscriber\IChannelSubscriber;
use Swoole\Coroutine\Redis;
use Swoole\WebSocket\Server as SwooleServer;
use Throwable;

class RedisChannelSubscriber
{

	/** @var IChannelSubscriber[] */
	private $channelSubscribers;

	/** @var Redis */
	private Redis $redis;

	/** @var string|null */
	private ?string $host;

	/** @var int|null */
	private ?int $port;

	/** @param IChannelSubscriber[] $channelSubscribers */
	public function __construct(array $channelSubscribers)
	{
		$this->redis = new Redis();
		$this->channelSubscribers = $channelSubscribers;
	}

	public function setConnection(string $host, int $port): void
	{
		$this->host = $host;
		$this->port = $port;
	}

	public function subscribeChannels(SwooleServer $server): void
	{
		$channels = array_keys($this->channelSubscribers);
		echo sprintf("Subscribing the channels [%s]...\n", implode(', ', $channels));

		$this->redis->subscribe($channels);

		$tryNumber = 0;

		while ($data = $this->redis->recv()) {
			[$listName, $channel, $serializedMessage] = $data;

			if ($listName === 'message') {
				foreach ($this->channelSubscribers as $channelSubscriber) {
					try {
						$channelSubscriber->subscribe($server, $serializedMessage, $channel);
					} catch (Throwable $e) {
						// Redis time out? Continue.
						if ($tryNumber < 1) {
							$this->subscribeChannels($server);
							$tryNumber++;
						} else {
							throw $e;
						}
					}
				}
			}
		}

		// Receiving stopped? Continue.
		$this->subscribeChannels($server);
	}

	public function connectToRedis(): void
	{
		$this->redis->connect($this->host, $this->port);
	}

}
