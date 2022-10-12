<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\Latte;

use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider;
use SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider;
use SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer;
use Symfony\Component\Asset\Packages;
use Mockery;
use Symfony;
use SixtyEightPublishers;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

final class TagRendererTest extends TestCase
{
	public function testRenderScriptTagsWithDefaultAttributes(): void
	{
		$entryPointLookup = Mockery::mock(IEntryPointLookup::class);
		$entryPointLookupProvider = Mockery::mock(IEntryPointLookupProvider::class);
		$packages = Mockery::mock(Packages::class);

		$entryPointLookup->shouldReceive('getJsFiles')->with('my_entry')->once()->andReturn(
			['/build/file1.js', '/build/file2.js']
		);
		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with(null)->once()->andReturn(
			$entryPointLookup
		);

		$packages->shouldReceive('getUrl')->with('/build/file1.js', 'custom_package')->once()->andReturn(
			'http://localhost:8080/build/file1.js'
		);
		$packages->shouldReceive('getUrl')->with('/build/file2.js', 'custom_package')->once()->andReturn(
			'http://localhost:8080/build/file2.js'
		);

		$renderer = new TagRenderer($entryPointLookupProvider, $packages);
		$tags = $renderer->renderJsTags('my_entry', 'custom_package');

		Assert::contains('<script src="http://localhost:8080/build/file1.js"></script>', $tags);
		Assert::contains('<script src="http://localhost:8080/build/file2.js"></script>', $tags);
	}

	public function testRenderScriptTagsWithinAnEntryPointCollection(): void
	{
		$firstEntryPointLookup = Mockery::mock(IEntryPointLookup::class);
		$secondEntryPointLookup = Mockery::mock(IEntryPointLookup::class);
		$thirdEntryPointLookup = Mockery::mock(IEntryPointLookup::class);

		$entryPointLookupProvider = Mockery::mock(IEntryPointLookupProvider::class);
		$packages = Mockery::mock(Packages::class);

		$firstEntryPointLookup->shouldReceive('getJsFiles')->once()->andReturn(['/build/file1.js']);
		$secondEntryPointLookup->shouldReceive('getJsFiles')->once()->andReturn(['/build/file2.js']);
		$thirdEntryPointLookup->shouldReceive('getJsFiles')->once()->andReturn(['/build/file3.js']);

		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with(null)->once()->andReturn(
			$firstEntryPointLookup
		);
		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with('second')->once()->andReturn(
			$secondEntryPointLookup
		);
		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with('third')->once()->andReturn(
			$thirdEntryPointLookup
		);

		$packages->shouldReceive('getUrl')->with('/build/file1.js', 'custom_package')->once()->andReturn(
			'http://localhost:8080/build/file1.js'
		);
		$packages->shouldReceive('getUrl')->with('/build/file2.js', null)->once()->andReturn(
			'http://localhost:8080/build/file2.js'
		);
		$packages->shouldReceive('getUrl')->with('/build/file3.js', 'specific_package')->once()->andReturn(
			'http://localhost:8080/build/file3.js'
		);


		$renderer = new TagRenderer($entryPointLookupProvider, $packages, ['crossorigin' => 'anonymous']);


		Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file1.js"></script>',
			$renderer->renderJsTags('my_entry', 'custom_package')
		);

		Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file2.js"></script>',
			$renderer->renderJsTags('my_entry', null, 'second')
		);

		Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file3.js"></script>',
			$renderer->renderJsTags('my_entry', 'specific_package', 'third')
		);
	}

	public function testRenderScriptTagsWithHashes(): void
	{
		$entryPointLookup = Mockery::mock(
			IEntryPointLookup::class,
			IIntegrityDataProvider::class
		);
		$entryPointLookupProvider = Mockery::mock(IEntryPointLookupProvider::class);
		$packages = Mockery::mock(Packages::class);

		$entryPointLookup->shouldReceive('getJsFiles')->once()->andReturn([
			'/build/file1.js',
			'/build/file2.js',
		]);

		$entryPointLookup->shouldReceive('getIntegrityData')->once()->andReturn([
			'/build/file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
			'/build/file2.js' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
		]);

		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with(null)->once()->andReturn(
			$entryPointLookup
		);
		$packages->shouldReceive('getUrl')->with('/build/file1.js', 'custom_package')->once()->andReturn(
			'http://localhost:8080/build/file1.js'
		);
		$packages->shouldReceive('getUrl')->with('/build/file2.js', 'custom_package')->once()->andReturn(
			'http://localhost:8080/build/file2.js'
		);

		$renderer = new TagRenderer($entryPointLookupProvider, $packages, ['crossorigin' => 'anonymous']);
		$output = $renderer->renderJsTags('my_entry', 'custom_package');

		Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>',
			$output
		);

		Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
			$output
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void
	{
		parent::tearDown();

		Mockery::close();
	}
}

(new TagRendererTest())->run();
