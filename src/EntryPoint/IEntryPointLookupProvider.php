<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

interface IEntryPointLookupProvider
{
	public function getEntryPointLookup(?string $buildName = null): IEntryPointLookup;
}
