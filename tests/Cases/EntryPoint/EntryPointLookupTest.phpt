<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\EntryPoint;

use Nette;
use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Nette\Utils\Json;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException;
use Tester;
use SixtyEightPublishers;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class EntryPointLookupTest extends Tester\TestCase
{
	private mixed $json = <<<JSON
{
  "entrypoints": {
    "my_entry": {
      "js": [
        "file1.js",
        "file2.js"
      ],
      "css": [
        "styles.css",
        "styles2.css"
      ]
    },
    "other_entry": {
      "js": [
        "file1.js",
        "file3.js"
      ],
      "css": []
    }
  },
  "integrity": {
    "file1.js": "sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc",
    "styles.css": "sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"
  }
}
JSON;

	private ?string $entryPointFilename;
	private ?EntryPointLookup $entryPointLookup;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->entryPointFilename = $this->createJsonFile($this->json);
		$this->entryPointLookup = $this->createEntryPointLookup($this->entryPointFilename);
	}

	public function testGetJsFiles(): void
	{
		Assert::equal([ 'file1.js', 'file2.js' ], $this->entryPointLookup->getJsFiles('my_entry'));
		Assert::equal([], $this->entryPointLookup->getJsFiles('my_entry'));

		$this->entryPointLookup->reset();

		Assert::equal([ 'file1.js', 'file2.js' ], $this->entryPointLookup->getJsFiles('my_entry'));
	}

	public function testGetJsFilesReturnsUniqueFilesOnly(): void
	{
		Assert::equal([ 'file1.js', 'file2.js' ], $this->entryPointLookup->getJsFiles('my_entry'));
		Assert::equal([ 'file3.js' ], $this->entryPointLookup->getJsFiles('other_entry'));
	}

	public function testGetCssFiles(): void
	{
		Assert::equal([ 'styles.css', 'styles2.css' ], $this->entryPointLookup->getCssFiles('my_entry'));
	}

	public function testEmptyReturnOnValidEntryNoJsOrCssFile(): void
	{
		Assert::equal([], $this->entryPointLookup->getCssFiles('other_entry'));
	}

	public function testGetIntegrityData(): void
	{
		Assert::equal([
			'file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
			'styles.css' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
		], $this->entryPointLookup->getIntegrityData());
	}

	public function testMissingIntegrityData(): void
	{
		$this->entryPointLookup = $this->createEntryPointLookup(
			$this->createJsonFile('{ "entrypoints": { "other_entry": { "js": { } } } }')
		);

		Assert::equal([], $this->entryPointLookup->getIntegrityData());
	}

	public function testExceptionOnInvalidJson(): void
	{
		$filename = $this->createJsonFile('abcd');
		$this->entryPointLookup = $this->createEntryPointLookup($filename);

		Assert::exception(
			function () {
				$this->entryPointLookup->getCssFiles('an_entry');
			},
			InvalidStateException::class,
			sprintf('The entrypoints file "%s" is not valid JSON.', $filename)
		);
	}

	public function testExceptionOnMissingEntryPointsKeyInJson(): void
	{
		$filename = $this->createJsonFile('{}');
		$this->entryPointLookup = $this->createEntryPointLookup($filename);

		Assert::exception(
			function () {
				$this->entryPointLookup->getJsFiles('an_entry');
			},
			InvalidStateException::class,
			sprintf('Could not find an "entrypoints" key in the "%s" file.', $filename)
		);
	}

	public function testExceptionOnBadFilename(): void
	{
		$this->entryPointLookup = new EntryPointLookup(
			'foo',
			'/invalid/path/to/manifest.json'
		);

		Assert::exception(
			function () {
				$this->entryPointLookup->getJsFiles('an_entry');
			},
			InvalidStateException::class,
			'Could not find the entrypoints file from Webpack: the file "/invalid/path/to/manifest.json" does not exist.'
		);
	}

	public function testExceptionOnMissingEntry(): void
	{
		Assert::exception(
			function () {
				$this->entryPointLookup->getCssFiles('fake_entry');
			},
			EntryPointNotFoundException::class,
			sprintf('Could not find the entry "fake_entry" in "%s". Found: my_entry, other_entry.', $this->entryPointFilename)
		);
	}

	public function testExceptionOnEntryWithExtension(): void
	{
		Assert::exception(
			function () {
				$this->entryPointLookup->getJsFiles('my_entry.js');
			},
			EntryPointNotFoundException::class,
			'Could not find the entry "my_entry.js". Try "my_entry" instead (without the extension).'
		);
	}

	public function testCachingEntryPointLookupCacheMissed(): void
	{
		$filename = $this->createJsonFile($this->json);
		$cache = new Cache(new MemoryStorage(), 'foo');

		$this->entryPointLookup = $this->createEntryPointLookup($filename, $cache, 'build_name');

		Assert::equal(
			['file1.js', 'file2.js'],
			$this->entryPointLookup->getJsFiles('my_entry')
		);

		Assert::equal(
			Json::decode($this->json, Json::FORCE_ARRAY),
			$cache->load('build_name')
		);
	}

	public function testCachingEntryPointLookupCacheHit(): void
	{
		$filename = $this->createJsonFile($this->json);
		$cache = new Cache(new MemoryStorage(), 'foo');

		$this->entryPointLookup = $this->createEntryPointLookup($filename, $cache, 'build_name');
		$cache->save('build_name', Json::decode($this->json, Json::FORCE_ARRAY));

		Assert::equal(
			['file1.js', 'file2.js'],
			$this->entryPointLookup->getJsFiles('my_entry')
		);
	}

	private function createEntryPointLookup(string $file, ?Cache $cache = NULL, string $name = 'foo'): EntryPointLookup
	{
		return  new EntryPointLookup($name, $file, $cache);
	}

	private function createJsonFile(string $json): string
	{
		return Tester\FileMock::create($json, 'json');
	}
}

(new EntryPointLookupTest())->run();
