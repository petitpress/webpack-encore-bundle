<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Latte;

use Latte\Extension;

class WebpackEncoreLatteExtension extends Extension
{
	public function __construct(
		private string $encoreCssNodeName,
		private string $encoreJsNodeName
	) {
	}

	public function getTags(): array
	{
		return [
			$this->encoreCssNodeName => [Nodes\EncoreCss::class, 'create'],
			$this->encoreJsNodeName => [Nodes\EncoreJs::class, 'create'],
		];
	}
}
