<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

interface IEntryPointLookup
{
	public function getBuildName(): string;

	public function getJsFiles(string $entryName): array;

	public function getCssFiles(string $entryName): array;

	public function reset(): void;
}
