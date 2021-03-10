<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use Swoole\WebSocket\Server as SwooleServer;

class Server extends SwooleServer
{

	/**
	 * @param mixed[]|null $options
	 * @see https://www.swoole.co.uk/docs/modules/swoole-server/configuration
	 */
	public function __construct(string $host, int $port, ?array $options = null)
	{
		parent::__construct($host, $port);

		if ($options !== null) {
			$this->set($options);
		}
	}

	public function onWorkerStart(callable $callback): void
	{
		$this->on('workerStart', $callback);
	}

	public function onOpen(callable $callback): void
	{
		$this->on('open', $callback);
	}

	public function onClose(callable $callback): void
	{
		$this->on('close', $callback);
	}

	public function onMessage(callable $callback): void
	{
		$this->on('message', $callback);
	}

	public function onWorkerStop(callable $callback): void
	{
		$this->on('workerStop', $callback);
	}

}
