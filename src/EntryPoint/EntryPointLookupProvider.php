<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

use Nette\SmartObject;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;

final class EntryPointLookupProvider implements IEntryPointLookupProvider
{
	use SmartObject;

	private array $lookups = [];
	private ?string $defaultName;

	/**
	 * @param IEntryPointLookup[] $lookups
	 * @param string|null         $defaultName
	 */
	public function __construct(array $lookups, ?string $defaultName = null)
	{
		foreach ($lookups as $lookup) {
			$this->add($lookup);
		}

		$this->defaultName = $defaultName;
	}

	private function add(IEntryPointLookup $lookup): void
	{
		$this->lookups[$lookup->getBuildName()] = $lookup;
	}

	/************** interface \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider **************/

	public function getEntryPointLookup(string $buildName = null): IEntryPointLookup
	{
		$buildName = $buildName ?? $this->defaultName;

		if (null === $buildName) {
			throw new EntryPointNotFoundException('There is no default build configured: please pass an argument to getEntryPointLookup().');
		}

		if (!isset($this->lookups[$buildName])) {
			throw new EntryPointNotFoundException(sprintf(
				'The build "%s" is not configured.',
				$buildName
			));
		}

		return $this->lookups[$buildName];
	}
}
