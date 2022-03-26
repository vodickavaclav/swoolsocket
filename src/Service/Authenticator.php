<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use Swoole\Http\Request;
use Swoole\WebSocket\Server as SwooleServer;

class Authenticator
{

	public const AUTH_TOKEN_PARAMETER = 'auth_token';

	private ConnectionStorage $connectionStorage;

	public function __construct(ConnectionStorage $connectionStorage)
	{
		$this->connectionStorage = $connectionStorage;
	}

	public function authenticate(Request $request, SwooleServer $server): void
	{
		if (!isset($request->get) || empty($request->get[self::AUTH_TOKEN_PARAMETER])) {
			$server->disconnect($request->fd, 401, 'Auth token is missing.');
			return;
		}

		$token = $request->get[self::AUTH_TOKEN_PARAMETER];
		$connection = $this->connectionStorage->getConnectionByHash($token);

		if ($connection === null) {
			$server->disconnect($request->fd, 401, 'Invalid token.');
			return;
		}

		$this->connectionStorage->addConnectionHash($request->fd, $connection->getHash());
	}

}
