<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\EntryPoint;

use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;
use Tester;
use Mockery;
use SixtyEightPublishers;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

final class EntryPointLookupProviderTest extends TestCase
{
	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void
	{
		parent::tearDown();

		Mockery::close();
	}

	public function testExceptionOnMissingBuildEntry(): void
	{
		$provider = new EntryPointLookupProvider([]);

		Tester\Assert::exception(
			static function () use ($provider) {
				$provider->getEntryPointLookup('foo');
			},
			EntryPointNotFoundException::class,
			'The build "foo" is not configured.'
		);
	}

	public function testExceptionOnMissingDefaultBuildEntry(): void
	{
		$provider = new EntryPointLookupProvider([]);

		Tester\Assert::exception(
			static function () use ($provider) {
				$provider->getEntryPointLookup();
			},
			EntryPointNotFoundException::class,
			'There is no default build configured: please pass an argument to getEntryPointLookup().'
		);
	}

	public function testBuildIsReturned(): void
	{
		$lookup = Mockery::mock(IEntryPointLookup::class);

		$lookup->shouldReceive('getBuildName')
			->andReturn('foo');

		$provider = new EntryPointLookupProvider([ $lookup ]);

		Tester\Assert::same($lookup, $provider->getEntryPointLookup('foo'));
	}

	public function testDefaultBuildIsReturned(): void
	{
		$lookup = Mockery::mock(IEntryPointLookup::class);

		$lookup->shouldReceive('getBuildName')
			->andReturn('_default');

		$provider = new EntryPointLookupProvider(
			[ $lookup ],
			'_default'
		);

		Tester\Assert::same($lookup, $provider->getEntryPointLookup());
	}
}

(new EntryPointLookupProviderTest())->run();
