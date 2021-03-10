<?php declare(strict_types = 1);

namespace vavo\SwoolSocket;

use vavo\SwoolSocket\Service\ConnectionStorage;
use vavo\SwoolSocket\Service\RedisChannelSubscriber;
use vavo\SwoolSocket\Service\Server;
use stdClass;
use Swoole\Http\Request;
use Swoole\Runtime;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as SwooleServer;

require __DIR__ . '/../vendor/autoload.php';


class Application
{

	/** @var Server|null */
	private $server;

	/** @var stdClass */
	private $websocketConfig;

	/**
	 * @var ConnectionStorage
	 */
	private ConnectionStorage $connectionStorage;

	/**
	 * @var RedisChannelSubscriber
	 */
	private RedisChannelSubscriber $redisChannelSubscriber;

	public function __construct(stdClass $websocketConfig, ConnectionStorage $connectionStorage, RedisChannelSubscriber $redisChannelSubscriber)
	{
		$this->websocketConfig = $websocketConfig;
		$this->connectionStorage = $connectionStorage;
		$this->redisChannelSubscriber = $redisChannelSubscriber;
	}

	public function run(): void
	{
		Runtime::enableCoroutine(true);

		$this->server = new Server(
			$this->websocketConfig->host,
			$this->websocketConfig->port,
			$this->websocketConfig->options ? (array) $this->websocketConfig->options : null
		);

		$this->bindWorkerEvents();
		$this->bindOpenConnectionEvents();
		$this->bindCloseConnectionEvents();
		$this->bindReceivedMessageEvents();

		$this->server->start();
	}

	private function bindWorkerEvents(): void
	{
		$this->server->onWorkerStart(function (SwooleServer $server, $workerId): void {
			echo sprintf("Swoole WebSocket Server #%s is started at http:///%s:%s\n", $workerId, $this->websocketConfig->host, $this->websocketConfig->port);

			if (!empty($this->websocketConfig->redis)) {
				// Connect to redis and subscribe defined channels
				$this->redisChannelSubscriber->connectToRedis($this->websocketConfig->redis->host, $this->websocketConfig->redis->port);
				$this->redisChannelSubscriber->subscribeChannels($server);
			}
		});
	}

	private function bindOpenConnectionEvents(): void
	{
		$this->server->onOpen(function (SwooleServer $server, Request $request): void {
			echo sprintf("Connection %s opened. \n", $request->fd);

			$this->connectionStorage->saveConnectionFromRequest($request);
		});
	}

	private function bindCloseConnectionEvents(): void
	{
		$this->server->onClose(function (SwooleServer $server, int $fd): void {
			echo sprintf("Connection %s closed.\n", $fd);

			$this->connectionStorage->removeConnectionHash($fd);
		});
	}

	private function bindReceivedMessageEvents(): void
	{
		$this->server->onMessage(function (SwooleServer $server, Frame $frame): void {
			echo sprintf("Received message: %s\n", $frame->data);
		});
	}

}
