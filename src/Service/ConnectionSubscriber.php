<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use Contributte\Events\Extra\Event\Application\StartupEvent;
use Nette\Security\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConnectionSubscriber implements EventSubscriberInterface
{

	/** @var User */
	private User $user;

	/** @var ConnectionStorage */
	private ConnectionStorage $connectionStorage;

	private AuthTokenStorage $authTokenStorage;

	public function __construct(User $user, ConnectionStorage $connectionStorage, AuthTokenStorage $authTokenStorage)
	{
		$this->user = $user;
		$this->connectionStorage = $connectionStorage;
		$this->authTokenStorage = $authTokenStorage;
	}

	/**
	 * @return string[]
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			StartupEvent::class => 'generateConnection',
		];
	}

	public function generateConnection(StartupEvent $event): void
	{
		// Get connection ID from cookie
		$connectionHash = $this->authTokenStorage->getAuthToken();

		// Connection does not exists for the cookie value, so let's create a new cookie value
		if ($connectionHash !== null && $this->connectionStorage->getConnectionByHash($connectionHash) === null) {
			$connectionHash = null;
		}

		// If user is logged in and connection ID not exists, create new connection and cookie identifier
		if ($connectionHash === null && $this->user->isLoggedIn()) {
			$connection = $this->connectionStorage->createConnection($this->user->getId());
			$connectionHash = $connection->getHash();

			$this->authTokenStorage->saveAuthToken($connectionHash);
		}
	}

}
