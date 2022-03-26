<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use Nette\Http\Session;
use Nette\Http\SessionSection;

class AuthTokenStorage
{

	public const STORAGE_NAME = 'websocket_auth_token';

	/** @var SessionSection */
	private $storage;

	public function __construct(Session $session)
	{
		$this->storage = $session->getSection(self::STORAGE_NAME);
	}

	public function getAuthToken(): ?string
	{
		return $this->storage->get(Authenticator::AUTH_TOKEN_PARAMETER);
	}

	public function saveAuthToken(string $token): void
	{
		$this->storage->set(
			Authenticator::AUTH_TOKEN_PARAMETER,
			$token,
			ConnectionStorage::CACHE_EXPIRE // Use the same expiration as connection storage
		);
	}

}
