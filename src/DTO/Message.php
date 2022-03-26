<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\DTO;

class Message
{

	/** @var string */
	private $channel;

	/** @var mixed[] */
	private $params;

	/** @var int|string|null */
	private $topicId;

	/**
	 * @param int|string|null $topicId
	 * @param mixed[] $params
	 */
	public function __construct(string $channel, array $params, $topicId = null)
	{
		$this->channel = $channel;
		$this->params = $params;
		$this->topicId = $topicId;
	}

	public function getChannel(): string
	{
		return $this->channel;
	}

	/** @return mixed[] */
	public function getParams(): array
	{
		return $this->params;
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
