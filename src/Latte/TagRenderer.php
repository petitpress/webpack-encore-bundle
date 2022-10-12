<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Latte;

use Nette;
use Throwable;
use Nette\Utils\Html;
use Symfony\Component\Asset\Packages;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider;

final class TagRenderer
{
	use Nette\SmartObject;

	private IEntryPointLookupProvider $entryPointLookupProvider;
	private Packages $packages;
	private array $defaultAttributes;

	public function __construct(
		IEntryPointLookupProvider $entryPointLookupProvider,
		Packages $packages,
		array $defaultAttributes = []
	) {
		$this->entryPointLookupProvider = $entryPointLookupProvider;
		$this->packages = $packages;
		$this->defaultAttributes = $defaultAttributes;
	}

	/**
	 * @throws Throwable
	 */
	public function renderJsTags(string $entryName, ?string $packageName = null, ?string $buildName = NULL): string
	{
		$entryPointLookup = $this->entryPointLookupProvider->getEntryPointLookup($buildName);
		$integrityHashes = ($entryPointLookup instanceof IIntegrityDataProvider) ? $entryPointLookup->getIntegrityData() : [];
		$htmlTag = Html::el('script')->addAttributes($this->defaultAttributes);

		foreach (($tags = $entryPointLookup->getJsFiles($entryName)) as $i => $file) {
			$tags[$i] = (clone $htmlTag)
				->addAttributes([
					'src' => $this->packages->getUrl($file, $packageName),
					'integrity' => $integrityHashes[$file] ?? null,
				])
				->render();
		}

		return implode("\n", $tags);
	}

	/**
	 * @throws Throwable
	 */
	public function renderCssTags(string $entryName, ?string $packageName = null, ?string $buildName = NULL): string
	{
		$entryPointLookup = $this->entryPointLookupProvider->getEntryPointLookup($buildName);
		$integrityHashes = ($entryPointLookup instanceof IIntegrityDataProvider) ? $entryPointLookup->getIntegrityData() : [];

		$htmlTag = Html::el('link')
			->addAttributes($this->defaultAttributes)
			->setAttribute('rel', 'stylesheet');

		foreach (($tags = $entryPointLookup->getCssFiles($entryName)) as $i => $file) {
			$tags[$i] = (clone $htmlTag)
				->addAttributes([
					'href' => $this->packages->getUrl($file, $packageName),
					'integrity' => $integrityHashes[$file] ?? null,
				])
				->render();
		}

		return implode("\n", $tags);
	}
}
