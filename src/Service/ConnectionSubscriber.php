<?php declare(strict_types = 1);

namespace vavo\SwoolSocket\Service;

use Contributte\Events\Extra\Event\Application\StartupEvent;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConnectionSubscriber implements EventSubscriberInterface
{

	/**
	 * @var User
	 */
	private User $user;

	/**
	 * @var IRequest
	 */
	private IRequest $request;

	/**
	 * @var ConnectionStorage
	 */
	private ConnectionStorage $connectionStorage;

	/**
	 * @var IResponse
	 */
	private IResponse $response;

	public function __construct(User $user, IRequest $request, IResponse $response, ConnectionStorage $connectionStorage)
	{
		$this->user = $user;
		$this->request = $request;
		$this->connectionStorage = $connectionStorage;
		$this->response = $response;
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

	public function generateConnection(): void
	{
		// Get connection ID from cookie
		$connectionHash = $this->request->getCookie(ConnectionStorage::COOKIE_NAME);

		// Connection does not exists for the cookie value, so let's create a new cookie value
		if ($connectionHash !== null && $this->connectionStorage->getConnectionByHash($connectionHash) === null) {
			$connectionHash = null;
		}

		// If user is logged in and connection ID not exists, create new connection and cookie identifier
		if ($connectionHash === null && $this->user->isLoggedIn()) {
			$connection = $this->connectionStorage->createConnection($this->user->getId());
			$this->response->setCookie(ConnectionStorage::COOKIE_NAME, $connection->getHash(), ConnectionStorage::CACHE_EXPIRE);
		}
	}

}
