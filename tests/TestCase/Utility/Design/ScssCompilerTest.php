<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Design;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Design\ScssCompiler;
use Awyiss\Utility\Design\ScssFilesCollection;
use Awyiss\Utility\Inflector;
use InvalidArgumentException;
use ScssPhp\ScssPhp\CompilationResult;
use SplFileInfo;


/**
 * Test case for ScssCompiler
 *
 * @see \Awyiss\Utility\Design\ScssCompiler
 */
class ScssCompilerTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up compiled CSS files
		$testCssPath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		if (file_exists($testCssPath)) {
			unlink($testCssPath);
		}
		if (file_exists($testCssPath . '.map')) {
			unlink($testCssPath . '.map');
		}

		$testCssPath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'subfolder' . DS . 'test.css';
		if (file_exists($testCssPath)) {
			unlink($testCssPath);
		}
		if (file_exists($testCssPath . '.map')) {
			unlink($testCssPath . '.map');
		}

		$testScssPath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test_all_variables.scss';
		if (file_exists($testScssPath)) {
			unlink($testScssPath);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::discoverRealmFiles()
	 */
	public function testDiscoverRealmFiles(): void {
		$files = ScssCompiler::discoverRealmFiles(Awyiss::REALM_FRONTEND);
		$this->assertCount(1, $files);
		$this->assertArrayHasKey(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, $files);

		$files = $files[ ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS ];
		$this->assertInstanceOf(ScssFilesCollection::class, $files);
		$this->assertCount(6, $files->getFiles());
		$this->assertCount(5, $files->getMainFiles());

		$files = ScssCompiler::discoverRealmFiles(Awyiss::REALM_BACKEND);
		$this->assertCount(2, $files);
		$this->assertArrayHasKey(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'awyiss' . DS, $files);
		$this->assertArrayHasKey(ROOT . DS . 'awyiss' . DS . 'assets' . DS, $files);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::discoverRealmFiles()
	 */
	public function testDiscoverRealmFilesWithInvalidRealm(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The given realm `invalid_realm` is invalid.');

		ScssCompiler::discoverRealmFiles('invalid_realm');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::discoverFiles()
	 */
	public function testDiscoverFiles(): void {
		$files = ScssCompiler::discoverFiles(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss');

		$this->assertCount(6, $files->getFiles());
		$this->assertCount(5, $files->getMainFiles());

		$this->assertSame('dummy_webfont.scss', $files->getFiles()[0]->getFilename());
		$this->assertSame(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'dummy_webfont.scss', $files->getFiles()[0]->getRealPath());

		$this->assertSame('main.scss', $files->getFiles()[1]->getFilename());
		$this->assertSame(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'main.scss', $files->getFiles()[1]->getRealPath());

		$this->assertSame('test.scss', $files->getFiles()[2]->getFilename());
		$this->assertSame(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'subfolder' . DS . 'test.scss', $files->getFiles()[2]->getRealPath());

		$this->assertSame('test.scss', $files->getFiles()[3]->getFilename());
		$this->assertSame(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss', $files->getFiles()[3]->getRealPath());

		$this->assertSame('webfonts.scss', $files->getFiles()[4]->getFilename());
		$this->assertSame(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'webfonts.scss', $files->getFiles()[4]->getRealPath());

		$this->assertSame('_variables.scss', $files->getFiles()[5]->getFilename());
		$this->assertSame(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables.scss', $files->getFiles()[5]->getRealPath());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::discoverFiles()
	 */
	public function testDiscoverFilesWithInvalidPath(): void {
		$files = ScssCompiler::discoverFiles('unknown_path');

		$this->assertCount(0, $files->getFiles());
		$this->assertCount(0, $files->getMainFiles());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compile()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompile(): void {
		$files = ScssCompiler::discoverFiles(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss');
		$compiledFiles = ScssCompiler::compile($files, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS);
		$this->assertCount(5, $compiledFiles);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[0]);
		$this->assertStringContainsString('* scss/dummy_webfont.scss' . PHP_EOL, $compiledFiles[0]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=dummy_webfont.css.map */', $compiledFiles[0]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'dummy_webfont.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);

		$this->assertStringContainsString('/*# sourceMappingURL=dummy_webfont.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[1]);
		$this->assertStringContainsString('* scss/main.scss', $compiledFiles[1]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=main.css.map */', $compiledFiles[1]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'main.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('/*# sourceMappingURL=main.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[2]);
		$this->assertStringContainsString('* scss/subfolder/test.scss', $compiledFiles[2]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $compiledFiles[2]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'subfolder' . DS . 'test.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {', $cssContent);
		$this->assertStringContainsString('width: 1280px;', $cssContent);
		$this->assertStringContainsString('padding-inline: 50px;', $cssContent);
		$this->assertStringContainsString('color: #e117d0;', $cssContent);
		$this->assertStringContainsString('font-family: "Roboto Serif", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[3]);
		$this->assertStringContainsString('* scss/test.scss', $compiledFiles[3]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $compiledFiles[3]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {', $cssContent);
		$this->assertStringContainsString('width: 1280px;', $cssContent);
		$this->assertStringContainsString('padding-inline: 50px;', $cssContent);
		$this->assertStringContainsString('color: #17bbe1;', $cssContent);
		$this->assertStringContainsString('font-family: "Comic Sans MS", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[4]);
		$this->assertStringContainsString('* scss/webfonts.scss', $compiledFiles[4]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=webfonts.css.map */', $compiledFiles[4]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'webfonts.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('/*# sourceMappingURL=webfonts.css.map */', $cssContent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compile()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileWithVars(): void {
		$files = ScssCompiler::discoverFiles(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss');
		$compiledFiles = ScssCompiler::compile($files, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [
			'colorMain' => '#FF0000',
			'fontNameMain' => '\'Open Sans\'',
			'pageWidth' => '960px',
		]);
		$this->assertCount(5, $compiledFiles);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[0]);
		$this->assertStringContainsString('* scss/dummy_webfont.scss' . PHP_EOL, $compiledFiles[0]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=dummy_webfont.css.map */', $compiledFiles[0]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'dummy_webfont.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('/*# sourceMappingURL=dummy_webfont.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[1]);
		$this->assertStringContainsString('* scss/main.scss', $compiledFiles[1]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=main.css.map */', $compiledFiles[1]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'main.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('/*# sourceMappingURL=main.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[2]);
		$this->assertStringContainsString('* scss/subfolder/test.scss', $compiledFiles[2]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $compiledFiles[2]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'subfolder' . DS . 'test.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {', $cssContent);
		$this->assertStringContainsString('width: 960px;', $cssContent);
		$this->assertStringContainsString('padding-inline: 50px;', $cssContent);
		$this->assertStringContainsString('color: #FF0000;', $cssContent);
		$this->assertStringContainsString('font-family: "Roboto Serif", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[3]);
		$this->assertStringContainsString('* scss/test.scss', $compiledFiles[3]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $compiledFiles[3]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {', $cssContent);
		$this->assertStringContainsString('width: 960px;', $cssContent);
		$this->assertStringContainsString('padding-inline: 50px;', $cssContent);
		$this->assertStringContainsString('color: #FF0000;', $cssContent);
		$this->assertStringContainsString('font-family: "Open Sans", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $cssContent);

		$this->assertInstanceOf(CompilationResult::class, $compiledFiles[4]);
		$this->assertStringContainsString('* scss/webfonts.scss', $compiledFiles[4]->getCss());
		$this->assertStringContainsString('/*# sourceMappingURL=webfonts.css.map */', $compiledFiles[4]->getCss());

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'webfonts.css';
		$this->assertFileExists($filePath);
		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('/*# sourceMappingURL=webfonts.css.map */', $cssContent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compile()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileReturnsCSS(): void {
		$files = ScssCompiler::discoverFiles(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss');
		$compiledFiles = ScssCompiler::compile($files, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], true);

		$this->assertCount(5, $compiledFiles);

		$cssContent = $compiledFiles[0];
		$this->assertStringContainsString('* scss/dummy_webfont.scss' . PHP_EOL, $cssContent);
		$this->assertStringNotContainsString('/*# sourceMappingURL=', $cssContent);

		$cssContent = $compiledFiles[1];
		$this->assertStringContainsString('* scss/main.scss', $cssContent);
		$this->assertStringNotContainsString('/*# sourceMappingURL=', $cssContent);

		$cssContent = $compiledFiles[2];
		$this->assertStringContainsString('* scss/subfolder/test.scss', $cssContent);
		$this->assertStringContainsString('.foo {', $cssContent);
		$this->assertStringContainsString('width: 1280px;', $cssContent);
		$this->assertStringContainsString('padding-inline: 50px;', $cssContent);
		$this->assertStringContainsString('color: #e117d0;', $cssContent);
		$this->assertStringContainsString('font-family: "Roboto Serif", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringNotContainsString('/*# sourceMappingURL=', $cssContent);

		$cssContent = $compiledFiles[3];
		$this->assertStringContainsString('* scss/test.scss', $cssContent);
		$this->assertStringContainsString('.foo {', $cssContent);
		$this->assertStringContainsString('width: 1280px;', $cssContent);
		$this->assertStringContainsString('padding-inline: 50px;', $cssContent);
		$this->assertStringContainsString('color: #17bbe1;', $cssContent);
		$this->assertStringContainsString('font-family: "Comic Sans MS", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringNotContainsString('/*# sourceMappingURL=', $cssContent);

		$cssContent = $compiledFiles[4];
		$this->assertStringContainsString('* scss/webfonts.scss', $cssContent);
		$this->assertStringNotContainsString('/*# sourceMappingURL=', $cssContent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compile()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileContainsColumnSystem(): void {
		$files = ScssCompiler::discoverFiles(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss');
		$compiledFiles = ScssCompiler::compile($files, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], true);

		$this->assertCount(5, $compiledFiles);

		// Test file in the subfolder must not contain the column system ($includeColumnSystem:false)
		$this->assertStringNotContainsString('@layer columnSystem {', $compiledFiles[2]);
		$this->assertStringNotContainsString('--columnWidth', $compiledFiles[2]);

		// Test file in the main folder must contain the column system ($includeColumnSystem:true)
		$this->assertStringContainsString('@layer columnSystem {', $compiledFiles[3]);
		$this->assertStringContainsString('    --columnWidth100: 100%;' . PHP_EOL
			. '    --columnWidth20: 17.6%;' . PHP_EOL
			. '    --columnWidth25: 22.75%;' . PHP_EOL
			. '    --columnWidth33: 31.33%;' . PHP_EOL
			. '    --columnWidth40: 38.2%;' . PHP_EOL
			. '    --columnWidth50: 48.5%;' . PHP_EOL
			. '    --columnWidth60: 58.8%;' . PHP_EOL
			. '    --columnWidth67: 65.66%;' . PHP_EOL
			. '    --columnWidth75: 74.25%;' . PHP_EOL
			. '    --columnWidth80: 79.4%;' . PHP_EOL
			. '    --columnIndent20: 20.6%;' . PHP_EOL
			. '    --columnIndent25: 25.75%;' . PHP_EOL
			. '    --columnIndent33: 34.33%;' . PHP_EOL
			. '    --columnIndent40: 41.2%;' . PHP_EOL
			. '    --columnIndent50: 51.5%;' . PHP_EOL
			. '    --columnIndent60: 61.8%;' . PHP_EOL
			. '    --columnIndent67: 68.66%;' . PHP_EOL
			. '    --columnIndent75: 77.25%;' . PHP_EOL
			. '    --columnIndent80: 82.4%;', $compiledFiles[3]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compile()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileForUnknownPath(): void {
		$files = ScssCompiler::discoverFiles('unknown_path');
		$compiledFiles = ScssCompiler::compile($files, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS);
		$this->assertNull($compiledFiles);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileFolders()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileFolders(): void {
		$files = ScssCompiler::discoverRealmFiles(Awyiss::REALM_FRONTEND);

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileDoesNotExist($filePath);

		ScssCompiler::compileFolders($files);

		$this->assertFileExists($filePath);

		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {' . PHP_EOL, $cssContent);
		$this->assertStringContainsString('color: #17bbe1;', $cssContent);
		$this->assertStringContainsString('font-family: "Comic Sans MS", Arial, Geneva, sans-serif;', $cssContent);
		$this->assertStringContainsString('/*# sourceMappingURL=test.css.map */', $cssContent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileFolders()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileFoldersWithVars(): void {
		$files = ScssCompiler::discoverRealmFiles(Awyiss::REALM_FRONTEND);

		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileDoesNotExist($filePath);

		ScssCompiler::compileFolders($files, [
			'colorMain' => '#00FF00',
			'fontNameMain' => '\'Source Sans Pro\'',
		]);

		$this->assertFileExists($filePath);

		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {' . PHP_EOL, $cssContent);
		$this->assertStringContainsString('color: #00FF00;', $cssContent);
		$this->assertStringContainsString('font-family: "Source Sans Pro", Arial, Geneva, sans-serif;', $cssContent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileFolders()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileFoldersWithInvalidValue(): void {
		/** @noinspection PhpParamsInspection */
		$compiled = ScssCompiler::compileFolders([
			'path' => 'invalid_value',
		]);

		$this->assertSame([], $compiled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScss(): void {
		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileDoesNotExist($filePath);

		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss');
		$compiled = ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], false);

		$this->assertInstanceOf(CompilationResult::class, $compiled);
		$this->assertFileExists($filePath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssWithVars(): void {
		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileDoesNotExist($filePath);

		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss');
		$compiled = ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [
			'colorMain' => '#0000FF',
			'fontNameMain' => 'Roboto',
		], false);

		$this->assertInstanceOf(CompilationResult::class, $compiled);
		$this->assertFileExists($filePath);

		$cssContent = file_get_contents($filePath);
		$this->assertStringContainsString('.foo {' . PHP_EOL, $cssContent);
		$this->assertStringContainsString('color: #0000FF;', $cssContent);
		$this->assertStringContainsString('font-family: "Roboto", Arial, Geneva, sans-serif;', $cssContent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssReturnsCSS(): void {
		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.css';
		$this->assertFileDoesNotExist($filePath);

		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss');
		$compiled = ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], true);

		$this->assertIsString($compiled);
		$this->assertFileDoesNotExist($filePath);

		$this->assertStringContainsString('.foo {' . PHP_EOL, $compiled);
		$this->assertStringContainsString('color: #17bbe1;', $compiled);
		$this->assertStringContainsString('font-family: "Comic Sans MS", Arial, Geneva, sans-serif;', $compiled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssIncludesColumnSystem(): void {
		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss');
		$compiled = ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], true, true);

		$this->assertIsString($compiled);

		$this->assertStringContainsString('@layer columnSystem {', $compiled);
		$this->assertStringContainsString('    --columnWidth100: 100%;' . PHP_EOL
			. '    --columnWidth20: 17.6%;' . PHP_EOL
			. '    --columnWidth25: 22.75%;' . PHP_EOL
			. '    --columnWidth33: 31.33%;' . PHP_EOL
			. '    --columnWidth40: 38.2%;' . PHP_EOL
			. '    --columnWidth50: 48.5%;' . PHP_EOL
			. '    --columnWidth60: 58.8%;' . PHP_EOL
			. '    --columnWidth67: 65.66%;' . PHP_EOL
			. '    --columnWidth75: 74.25%;' . PHP_EOL
			. '    --columnWidth80: 79.4%;' . PHP_EOL
			. '    --columnIndent20: 20.6%;' . PHP_EOL
			. '    --columnIndent25: 25.75%;' . PHP_EOL
			. '    --columnIndent33: 34.33%;' . PHP_EOL
			. '    --columnIndent40: 41.2%;' . PHP_EOL
			. '    --columnIndent50: 51.5%;' . PHP_EOL
			. '    --columnIndent60: 61.8%;' . PHP_EOL
			. '    --columnIndent67: 68.66%;' . PHP_EOL
			. '    --columnIndent75: 77.25%;' . PHP_EOL
			. '    --columnIndent80: 82.4%;', $compiled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssWithNonScssFile(): void {
		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'non_scss_file.txt');

		$this->expectException(InvalidArgumentException::class);
		ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssRemovesMinifiedCssFile(): void {
		$filePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'css' . DS . 'test.min.css';
		file_put_contents($filePath, 'Dummy');
		$this->assertFileExists($filePath);

		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss');
		ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], false);

		$this->assertFileDoesNotExist($filePath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssWithNonExistingFile(): void {
		$file = new SplFileInfo(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'non_existing_file.scss');

		$this->expectException(InvalidArgumentException::class);
		ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, [], false);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssCompiler::compileScss()
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function testCompileScssNormalizesVariables(): void {
		$vars = [
			'fontNameMain' => [
				'font' => [
					'category' => 'sans-serif',
					'id' => 'red-hat-text',
					'name' => 'Red Hat Text',
					'popularity' => 267,
					'variants' => [
						'300',
						'regular',
						'500',
						'600',
						'700',
						'300italic',
						'italic',
						'500italic',
						'600italic',
						'700italic',
					],
					'version' => 'v18',
				],
				'variants' => ['300', '300i', '400', '400i', '700', '700i'],
			],
			'fontStackFallbackMain' => 'Gill Sans, Arial, sans-serif',
			'fontWeightMain' => '300',
			'fontStyleMain' => 'normal',
			'fontSizeMain' => '18',
			'fontSizeMainUnit' => 'px',
			'lineHeightMain' => '1.5',
			'lineHeightMainUnit' => 'rem',
			'colorText' => '#123d0a',
			'colorDark' => '#101820',
			'colorMedium' => '#686e77',
			'colorLight' => '#f2f5f6',
			'colorBright' => '#FFFFFF',
			'colorMain' => '#61ac6a',
			'colorContrast' => '#b02ed1',
			'pageWidth' => '1440',
			'pageWidthUnit' => 'px',
			'pagePadding' => '50',
			'pagePaddingUnit' => 'px',
			'columnMargin' => '5',
			'columnMarginUnit' => '%',
			'menuBreakpoint' => '1024',
			'menuBreakpointUnit' => 'px',
			'singleColumnBreakpoint' => '860',
			'singleColumnBreakpointUnit' => 'px',
		];

		// Create a new tmp file for the test with all variables used
		$tmpContent = <<<'SCSS'
@import "variables";

.test-all-variables {
  // Font variables
  font-family: $fontStackMain;
  font-weight: $fontWeightMain;
  font-style: $fontStyleMain;
  font-size: $fontSizeMain;
  line-height: $lineHeightMain;
  
  // Colors
  color: $colorText;
  background-color: $colorLight;
  border-color: $colorMain;
  
  .color-variations {
    color: $colorDark;
    background: $colorMedium;
    border: 1px solid $colorContrast;
    box-shadow: 0 0 10px $colorBright;
  }
  
  // Layout
  max-width: $pageWidth;
  padding: $pagePadding;
  margin: 0 $columnMargin;
  
  @media (max-width: $menuBreakpoint) {
    padding: calc($pagePadding / 2);
  }
  
  @media (max-width: $singleColumnBreakpoint) {
    margin: 0 $columnMargin * .5;
  }
  
  :root {
    awyissVersion:$awyissVersion;
	awyissVersionMajor:$awyissVersionMajor;
	awyissVersionName:$awyissVersionName;
  }
}
SCSS;

		$tmpFilePath = ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test_all_variables.scss';
		file_put_contents($tmpFilePath, $tmpContent);
		$this->assertFileExists($tmpFilePath);

		$file = new SplFileInfo($tmpFilePath);
		$compiled = ScssCompiler::compileScss($file, ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS, $vars, true);

		$this->assertStringContainsString('font-family: "Red Hat Text", Gill Sans, Arial, sans-serif;', $compiled);
		$this->assertStringContainsString('font-weight: 300;', $compiled);
		$this->assertStringContainsString('font-size: 18px;', $compiled);
		$this->assertStringContainsString('line-height: 1.5rem;', $compiled);
		$this->assertStringContainsString('color: #123d0a;', $compiled);
		$this->assertStringContainsString('background-color: #f2f5f6;', $compiled);
		$this->assertStringContainsString('border-color: #61ac6a;', $compiled);
		$this->assertStringContainsString('max-width: 1440px;', $compiled);
		$this->assertStringContainsString('padding: 50px;', $compiled);
		$this->assertStringContainsString('margin: 0 5%;', $compiled);

		$this->assertStringContainsString('color: #101820;', $compiled);
		$this->assertStringContainsString('background: #686e77;', $compiled);
		$this->assertStringContainsString('border: 1px solid #b02ed1;', $compiled);
		$this->assertStringContainsString('box-shadow: 0 0 10px #FFFFFF;', $compiled);

		$this->assertStringContainsString('@media (max-width: 1024px) {', $compiled);
		$this->assertStringContainsString('padding: 25px;', $compiled);

		$this->assertStringContainsString('@media (max-width: 860px) {', $compiled);
		$this->assertStringContainsString('margin: 0 2.5%;', $compiled);

		$this->assertStringContainsString('awyissVersion: "' . Awyiss::VERSION . '"', $compiled);
		$this->assertStringContainsString('awyissVersionMajor: "' . explode('.', Awyiss::VERSION)[0] . '"', $compiled);
		$this->assertStringContainsString('awyissVersionName: "' . Inflector::dasherize(Awyiss::VERSION_NAME) . '"', $compiled);
	}
}
