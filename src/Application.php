<?php declare(strict_types = 1);

namespace vavo\SwoolSocket;

use vavo\SwoolSocket\Service\Authenticator;
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

	/** @var ConnectionStorage */
	private ConnectionStorage $connectionStorage;

	/** @var RedisChannelSubscriber */
	private RedisChannelSubscriber $redisChannelSubscriber;

	private Authenticator $authenticator;

	public function __construct(stdClass $websocketConfig, ConnectionStorage $connectionStorage, RedisChannelSubscriber $redisChannelSubscriber, Authenticator $authenticator)
	{
		$this->websocketConfig = $websocketConfig;
		$this->connectionStorage = $connectionStorage;
		$this->redisChannelSubscriber = $redisChannelSubscriber;
		$this->authenticator = $authenticator;
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
				$this->redisChannelSubscriber->setConnection($this->websocketConfig->redis->host, $this->websocketConfig->redis->port);
				$this->redisChannelSubscriber->connectToRedis();

				$this->redisChannelSubscriber->subscribeChannels($server);
			}
		});
	}

	private function bindOpenConnectionEvents(): void
	{
		$this->server->onOpen(function (SwooleServer $server, Request $request): void {
			echo sprintf("Connection %s opened. \n", $request->fd);
			$this->authenticator->authenticate($request, $server);
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
			// Check for a ping event using the OpCode
			if ($frame->opcode === WEBSOCKET_OPCODE_PING) {
				$pongFrame = new Frame();

				// Setup a new data frame to send back a pong to the client
				$pongFrame->opcode = WEBSOCKET_OPCODE_PONG;
				$server->push($frame->fd, $pongFrame);
			}

			echo sprintf("Received message: %s\n", $frame->data);
		});
	}

}
