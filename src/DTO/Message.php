<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\DTO;

class Message
{

	/** @var string */
	private $channel;

	/** @var string */
	private $body;

	/** @var int|string|null */
	private $topicId;

	/**
	 * @param int|string|null $topicId
	 */
	public function __construct(string $channel, string $body, $topicId = null)
	{
		$this->channel = $channel;
		$this->body = $body;
		$this->topicId = $topicId;
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	/**
	 * @return int|string|null
	 */
	public function getTopicId()
	{
		return $this->topicId;
	}

	/**
	 * @param int|string $topicId
	 */
	public function setTopicId($topicId): void
	{
		$this->topicId = $topicId;
	}

}
