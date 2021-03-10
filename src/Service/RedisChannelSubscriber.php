<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use vavo\SwoolSocket\Subscriber\IChannelSubscriber;
use Swoole\Coroutine\Redis;
use Swoole\WebSocket\Server as SwooleServer;

class RedisChannelSubscriber
{

	/** @var IChannelSubscriber[] */
	private $channelSubscribers;

	/**
	 * @var Redis
	 */
	private Redis $redis;

	/** @param IChannelSubscriber[] $channelSubscribers */
	public function __construct(array $channelSubscribers)
	{
		$this->redis = new Redis();
		$this->channelSubscribers = $channelSubscribers;
	}

	public function connectToRedis(string $host, int $port): void
	{
		$this->redis->connect($host, $port);
	}

	public function subscribeChannels(SwooleServer $server): void
	{
		$channels = array_keys($this->channelSubscribers);
		$this->redis->subscribe($channels);

		while ($data = $this->redis->recv()) {
			[$listName, $channel, $serializedMessage] = $data;

			if ($listName === 'message') {
				foreach ($this->channelSubscribers as $channelSubscriber) {
					$channelSubscriber->subscribe($server, $serializedMessage, $channel);
				}
			}
		}

		// Receiving stopped? Continue.
		$this->subscribeChannels($server);
	}

}
