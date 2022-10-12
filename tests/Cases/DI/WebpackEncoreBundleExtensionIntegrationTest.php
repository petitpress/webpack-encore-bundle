<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\DI;

use Tester\Assert;
use Tester\TestCase;
use Nette\Caching\Cache;
use SixtyEightPublishers\WebpackEncoreBundle\Tests\Helper\ContainerFactory;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider;

require __DIR__ . '/../../bootstrap.php';

class WebpackEncoreBundleExtensionIntegrationTest extends TestCase
{
	public function testRegisteredEntryPointLookupProviderService(): void
	{
		$container = ContainerFactory::createContainer(
			__METHOD__,
			__DIR__ . '/../../files/encore.neon'
		);

		Assert::noError(static function () use ($container) {
			$container->getService('encore.entryPointLookupProvider');
		});

		/** @var IEntryPointLookupProvider $entryPointLookupProvider */
		$entryPointLookupProvider = $container->getService('encore.entryPointLookupProvider');

		Assert::type(IEntryPointLookupProvider::class, $entryPointLookupProvider);

		Assert::noError(static function () use ($entryPointLookupProvider) {
			$entryPointLookupProvider->getEntryPointLookup();
			$entryPointLookupProvider->getEntryPointLookup('different_build');
		});
	}

	public function testRegisteredCacheService(): void
	{
		$container = ContainerFactory::createContainer(
			__METHOD__,
			__DIR__ . '/../../files/encore_cache_enabled.neon'
		);

		Assert::noError(static function () use ($container) {
			$container->getService('encore.cache.cache');
		});

		/** @var Cache $cache */
		$cache = $container->getService('encore.cache.cache');

		Assert::type(Cache::class, $cache);
	}
}

(new WebpackEncoreBundleExtensionIntegrationTest())->run();
