<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\DTO;

use Nette\Utils\Random;

class Connection
{

	/** @var string */
	private $hash;

	/** @var int|string */
	private $topicId;

	/**
	 * @param int|string $topicId
	 */
	public function __construct($topicId)
	{
		$this->hash = Random::generate();
		$this->topicId = $topicId;
	}

	/**
	 * @return int|string
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

	public function getHash(): string
	{
		return $this->hash;
	}

}
