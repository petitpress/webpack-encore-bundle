<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\DI;

use Latte\Engine;
use RuntimeException;
use Nette\Caching\Cache;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Strings;
use Nette\Caching\Storage;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Utils\AssertionException;
use Symfony\Component\Asset\Packages;
use Nette\DI\Extensions\InjectExtension;
use Nette\DI\Definitions\ServiceDefinition;
use SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Latte\WebpackEncoreLatteExtension;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider;

final class WebpackEncoreBundleExtension extends CompilerExtension
{
	private const ENTRYPOINTS_FILE_NAME = 'entrypoints.json';
	private const ENTRYPOINT_DEFAULT_NAME = '_default';
	private const CROSSORIGIN_ALLOWED_VALUES = [null, 'anonymous', 'use-credentials'];

	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'output_path' => Expect::string()->nullable(),
			# The path where Encore is building the assets - i.e. Encore.setOutputPath()
			'builds' => Expect::array()->items('string')->assert(function (array $value): bool {
				if (isset($value[self::ENTRYPOINT_DEFAULT_NAME])) {
					throw new AssertionException(
						sprintf('Key "%s" can\'t be used as build name.', self::ENTRYPOINT_DEFAULT_NAME)
					);
				}

				return true;
			}),
			'crossorigin' => Expect::string()->nullable()->assert(function (?string $value): bool {
				if (!in_array($value, self::CROSSORIGIN_ALLOWED_VALUES, true)) {
					throw new AssertionException(
						sprintf('Value "%s" for setting "crossorigin" is not allowed', $value)
					);
				}

				return true;
			}),
			# crossorigin value when Encore.enableIntegrityHashes() is used, can be null (default), anonymous or use-credentials
			'cache' => Expect::structure([
				'enabled' => Expect::bool(false),
				'storage' => Expect::string('@' . Storage::class)->dynamic(),
			]),
			'latte' => Expect::structure([
				'js_assets_macro_name' => Expect::string('encore_js'),
				'css_assets_macro_name' => Expect::string('encore_css'),
			]),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$cache = $this->registerCache($this->getConfig()->cache->enabled, $this->getConfig()->cache->storage);
		$lookups = [];

		if (null !== $this->getConfig()->output_path) {
			$lookups[] = $this->createEntryPointLookupStatement(
				self::ENTRYPOINT_DEFAULT_NAME,
				$this->getConfig()->output_path,
				$cache
			);
		}

		foreach ($this->getConfig()->builds as $name => $path) {
			$lookups[] = $this->createEntryPointLookupStatement($name, $path, $cache);
		}

		$builder->addDefinition($this->prefix('entryPointLookupProvider'))
			->setType(IEntryPointLookupProvider::class)
			->setFactory(EntryPointLookupProvider::class, [
				'lookups' => $lookups,
				'defaultName' => null !== $this->getConfig()->output_path ? self::ENTRYPOINT_DEFAULT_NAME : null,
			]);

		$defaultAttributes = [];

		if (null !== $this->getConfig()->crossorigin) {
			$defaultAttributes['crossorigin'] = $this->getConfig()->crossorigin;
		}

		$builder->addDefinition($this->prefix('tagRenderer'))
			->setType(TagRenderer::class)
			->setAutowired(false)
			->setArguments([
				'defaultAttributes' => $defaultAttributes,
			]);
	}

	private function registerCache(bool $enabled, mixed $storage): ?ServiceDefinition
	{
		if (false === $enabled) {
			return null;
		}

		$builder = $this->getContainerBuilder();

		if (!is_string($storage) || !Strings::startsWith($storage, '@')) {
			$storage = $builder->addDefinition($this->prefix('cache.storage'))
				->setType(Storage::class)
				->setFactory($storage)
				->addTag(InjectExtension::TAG_INJECT);
		}

		return $builder->addDefinition($this->prefix('cache.cache'))
			->setType(Cache::class)
			->setArguments([
				'storage' => $storage,
				'namespace' => str_replace('\\', '.', IEntryPointLookup::class),
			])
			->addTag(InjectExtension::TAG_INJECT);
	}

	private function createEntryPointLookupStatement(string $name, string $path, ?ServiceDefinition $cache): Statement
	{
		return new Statement(EntryPointLookup::class, [
			'buildName' => $name,
			'entryPointJsonPath' => $path . '/' . self::ENTRYPOINTS_FILE_NAME,
			'cache' => $cache,
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		if (null === $builder->getByType(Packages::class)) {
			throw new RuntimeException(
				'Missing service of type Symfony\Component\Asset\Packages that is required by this package. You can configure and register it manually or you can use package 68publishers/asset (recommended way).'
			);
		}

		$latteFactory = $builder->getDefinition($builder->getByType(Engine::class) ?? 'nette.latteFactory');

		$latteFactory->getResultDefinition()->addSetup('addProvider', [
			'name' => 'webpackEncoreTagRenderer',
			'value' => $this->prefix('@tagRenderer'),
		]);

		$latteFactory->getResultDefinition()->addSetup(
			'addExtension',
			[
				new Statement(
					WebpackEncoreLatteExtension::class,
					[
						'encoreCssNodeName' => $this->getConfig()->latte->css_assets_macro_name,
						'encoreJsNodeName' => $this->getConfig()->latte->js_assets_macro_name,
					]
				),
			]
		);
	}
}
