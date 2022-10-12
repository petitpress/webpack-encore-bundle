<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

use Throwable;
use Nette\Utils\Json;
use Nette\SmartObject;
use Nette\Caching\Cache;
use Nette\Utils\JsonException;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;

final class EntryPointLookup implements IEntryPointLookup, IIntegrityDataProvider
{
	use SmartObject;

	private string $buildName;
	private string $entryPointJsonPath;
	private ?Cache $cache;
	private ?array $entriesData = null;
	private array $returnedFiles = [];

	public function __construct(string $buildName, string $entryPointJsonPath, ?Cache $cache = null)
	{
		$this->buildName = $buildName;
		$this->entryPointJsonPath = $entryPointJsonPath;
		$this->cache = $cache;
	}

	/**
	 * @throws Throwable
	 */
	private function getEntryFiles(string $entryName, string $key): array
	{
		$entryData = $this->getEntryData($entryName);

		if (!isset($entryData[$key])) {
			return [];
		}

		$newFiles = array_values(array_diff($entryData[$key], $this->returnedFiles));
		$this->returnedFiles = array_merge($this->returnedFiles, $newFiles);

		return $newFiles;
	}

	/**
	 * @throws EntryPointNotFoundException
	 * @throws Throwable
	 */
	private function getEntryData(string $entryName): array
	{
		$entriesData = $this->getEntriesData();

		if (isset($entriesData['entrypoints'][$entryName])) {
			return $entriesData['entrypoints'][$entryName];
		}

		$withoutExtension = substr($entryName, 0, (int) strrpos($entryName, '.'));

		if (!empty($withoutExtension) && isset($entriesData['entrypoints'][$withoutExtension])) {
			throw new EntryPointNotFoundException(sprintf(
				'Could not find the entry "%s". Try "%s" instead (without the extension).',
				$entryName,
				$withoutExtension
			));
		}

		throw new EntryPointNotFoundException(sprintf(
			'Could not find the entry "%s" in "%s". Found: %s.',
			$entryName,
			$this->entryPointJsonPath,
			implode(', ', array_keys($entriesData['entrypoints']))
		));
	}

	/**
	 * @throws InvalidStateException
	 * @throws Throwable
	 */
	private function getEntriesData(): array
	{
		if (null !== $this->entriesData) {
			return $this->entriesData;
		}

		if (null !== $this->cache && is_array($entriesData = $this->cache->load($this->getBuildName()))) {
			return $this->entriesData = $entriesData;
		}

		if (!file_exists($this->entryPointJsonPath)) {
			throw new InvalidStateException(sprintf(
				'Could not find the entrypoints file from Webpack: the file "%s" does not exist.',
				$this->entryPointJsonPath
			));
		}

		try {
			$this->entriesData = Json::decode(file_get_contents($this->entryPointJsonPath), Json::FORCE_ARRAY);
		} catch (JsonException $e) {
			throw new InvalidStateException(sprintf(
				'The entrypoints file "%s" is not valid JSON.',
				$this->entryPointJsonPath
			), 0, $e);
		}

		if (!isset($this->entriesData['entrypoints'])) {
			throw new InvalidStateException(sprintf(
				'Could not find an "entrypoints" key in the "%s" file.',
				$this->entryPointJsonPath
			));
		}

		$this->cache?->save($this->getBuildName(), $this->entriesData);

		return $this->entriesData;
	}

	/*********************** interface \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup ***********************/

	public function getBuildName(): string
	{
		return $this->buildName;
	}

	/**
	 * @throws Throwable
	 */
	public function getJsFiles(string $entryName): array
	{
		return $this->getEntryFiles($entryName, 'js');
	}

	/**
	 * @throws Throwable
	 */
	public function getCssFiles(string $entryName): array
	{
		return $this->getEntryFiles($entryName, 'css');
	}

	public function reset(): void
	{
		$this->returnedFiles = [];
	}

	/*********************** interface \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider ***********************/

	/**
	 * @throws Throwable
	 */
	public function getIntegrityData(): array
	{
		$integrity = $this->getEntriesData()['integrity'] ?? [];

		return is_array($integrity) ? $integrity : [];
	}
}
