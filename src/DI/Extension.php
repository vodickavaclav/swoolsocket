<?php declare(strict_types = 1);


namespace vavo\SwoolSocket\DI;

use Nette\DI\CompilerExtension;
use vavo\SwoolSocket\Application;
use vavo\SwoolSocket\Service\Authenticator;
use vavo\SwoolSocket\Service\AuthTokenStorage;
use vavo\SwoolSocket\Service\ConnectionStorage;
use vavo\SwoolSocket\Service\ConnectionSubscriber;
use vavo\SwoolSocket\Service\RedisChannelSubscriber;
use vavo\SwoolSocket\Subscriber\IChannelSubscriber;
use Nette\DI\ServiceCreationException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class Extension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'host' => Expect::string()->default('0.0.0.0'),
			'port' => Expect::int()->default(9502),
			'options' => Expect::array(),
			'redis' => Expect::structure([
				'host' => Expect::string(),
				'port' => Expect::int()->default(6379),
			]),
		]);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$connectionStorage = $builder->addDefinition($this->prefix('storage'))
			->setFactory(ConnectionStorage::class, [
				'@\Nette\Caching\IStorage',
			]);

		$authTokenStorage = $builder->addDefinition($this->prefix('authTokenStorage'))
			->setFactory(AuthTokenStorage::class, ['@\Nette\Http\Session']);

		$builder->addDefinition($this->prefix('subscriber'))
			->setFactory(ConnectionSubscriber::class, [
				'@\Nette\Security\User',
				$connectionStorage,
				$authTokenStorage,
			]);

		$channelSubscribers = [];
		$channelSubscriberDefinitions = $builder->findByType(IChannelSubscriber::class);

		foreach ($channelSubscriberDefinitions as $service) {
			$name = call_user_func([$service->getType(), 'getChannelName']);

			if (isset($channelSubscribers[$name])) {
				throw new ServiceCreationException(
					sprintf(
						'Channel subscriber "%s" with channel name "%s" already exists.',
						$service->getType(),
						$name
					)
				);
			}

			$channelSubscribers[$name] = $service;
		}

		$redisChannelSubscriber = $builder->addDefinition($this->prefix('redisSubscriber'))
			->setFactory(RedisChannelSubscriber::class, [$channelSubscribers]);

		$builder->addDefinition($this->prefix('authenticator'))
			->setFactory(Authenticator::class, [$connectionStorage]);

		$builder->addDefinition($this->prefix($this->prefix('application')))
			->setFactory(Application::class, [$this->config, $connectionStorage, $redisChannelSubscriber]);
	}

}
