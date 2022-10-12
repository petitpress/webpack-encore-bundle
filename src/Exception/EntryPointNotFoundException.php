<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Exception;

use RuntimeException;

final class EntryPointNotFoundException extends RuntimeException implements IException
{
}
