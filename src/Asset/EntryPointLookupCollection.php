<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

use SixtyEightPublishers\WebpackEncoreBundle\Exception\UndefinedBuildException;

final class EntryPointLookupCollection implements EntryPointLookupCollectionInterface
{
	/** @var array<string, EntryPointLookupInterface> */
	private array $entryPointLookups;

	private ?string $defaultBuildName;

	/**
	 * @param array<string, EntryPointLookupInterface> $entryPointLookups
	 */
	public function __construct(array $entryPointLookups, ?string $defaultBuildName = NULL)
	{
		$this->entryPointLookups = $entryPointLookups;
		$this->defaultBuildName = $defaultBuildName;
	}

	public function getEntrypointLookup(?string $buildName = NULL): EntryPointLookupInterface
	{
		$buildName = $buildName ?? $this->defaultBuildName;

		if (NULL === $buildName) {
			throw UndefinedBuildException::defaultBuildNotConfigured();
		}

		if (!isset($this->entryPointLookups[$buildName])) {
			throw UndefinedBuildException::buildNotConfigured($buildName);
		}

		return $this->entryPointLookups[$buildName];
	}
}
