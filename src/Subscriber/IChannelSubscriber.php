<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Subscriber;

use vavo\SwoolSocket\DTO\Message;
use Swoole\WebSocket\Server as SwooleServer;

interface IChannelSubscriber
{

	public static function getChannelName(): string;
	public function createMessage(string $channel, string $serializedMessage): Message;
	public function subscribe(SwooleServer $server, string $serializedMessage, string $channel): void;

}
