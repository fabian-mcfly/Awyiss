<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Queue\Task\Design;


use Awyiss\Queue\Task\Design\WebfontDownloadTask;
use Awyiss\Test\TestSuite\TestCase;
use Symfony\Component\Process\Process;
use ZipArchive;


/**
 * Test case for WebfontDownloadTask
 *
 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask
 */
class WebfontDownloadTaskTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Cleanup previous output
		$this->rmdir();

		$scssPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'webfonts.scss';
		if (file_exists($scssPath)) {
			unlink($scssPath);
		}
	}


	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		$scssPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'webfonts.scss';
		if (!file_exists($scssPath)) {
			file_put_contents($scssPath, <<<'SCSS'
/**
 * scss/webfonts.scss
 * Dummy SCSS file
 */

SCSS);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::generateScssFile()
	 * @throws \ReflectionException
	 */
	public function testGenerateScssFileWithNoFonts(): void {
		$task = new WebfontDownloadTask();
		$scss = $this->callProtectedMethod(
			$task,
			'generateScssFile',
			[]
		);

		$this->assertStringContainsString('/**', $scss);
		// should not contain any font-face blocks
		$this->assertStringNotContainsString('@font-face', $scss);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::generateScssFile()
	 * @throws \ReflectionException
	 */
	public function testGenerateScssFileWithMultipleVariants(): void {
		// Copy `awyiss/assets/font/2f_media-webfont.woff2` to each font directory to simulate downloaded fonts
		$fontDir = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'font';
		$sourceFontFile = ROOT . DS . 'awyiss' . DS . 'assets' . DS . 'font' . DS . '2f_media-webfont.woff2';

		// Make sure all folders exist
		foreach (['font1', 'font2', 'font3'] as $fontId) {
			$dir = $fontDir . DS . $fontId;
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
		}

		copy($sourceFontFile, $fontDir . DS . 'font1' . DS . 'font1-v1-latin-regular.woff2');
		copy($sourceFontFile, $fontDir . DS . 'font1' . DS . 'font1-v1-latin-italic.woff2');
		copy($sourceFontFile, $fontDir . DS . 'font1' . DS . 'font1-v1-latin-700.woff2');
		copy($sourceFontFile, $fontDir . DS . 'font2' . DS . 'font2-v2-latin-regular.woff2');
		copy($sourceFontFile, $fontDir . DS . 'font2' . DS . 'font2-v2-latin-700.woff2');
		copy($sourceFontFile, $fontDir . DS . 'font3' . DS . 'font3-v3-latin-italic.woff2');
		copy($sourceFontFile, $fontDir . DS . 'font3' . DS . 'font3-v3-latin-300italic.woff2');
		// font3 does not have a 700 variant, so we won't copy that

		$task = new WebfontDownloadTask();
		$fonts = [
			[
				'id' => 'font1',
				'name' => 'Test Font',
				'version' => 'v1',
				'variants' => ['regular', 'italic', '700'],
			],
			[
				'id' => 'font2',
				'name' => 'Another Font',
				'version' => 'v2',
				'variants' => ['regular', '700'],
			],
			[
				'id' => 'font3',
				'name' => 'Third Font',
				'version' => 'v3',
				'variants' => ['italic', '300italic', '700'],
			],
		];
		$scss = $this->callProtectedMethod(
			$task,
			'generateScssFile',
			$fonts
		);

		// header exists
		$this->assertStringContainsString('Webfont SCSS file', $scss);

		$this->assertStringContainsString("src:url('../font/font1/font1-v1-latin-regular.woff2')", $scss);
		$this->assertStringContainsString("src:url('../font/font1/font1-v1-latin-italic.woff2')", $scss);
		$this->assertStringContainsString("src:url('../font/font1/font1-v1-latin-700.woff2')", $scss);

		$this->assertStringContainsString("src:url('../font/font2/font2-v2-latin-regular.woff2')", $scss);
		$this->assertStringContainsString("src:url('../font/font2/font2-v2-latin-700.woff2')", $scss);

		$this->assertStringContainsString("src:url('../font/font3/font3-v3-latin-italic.woff2')", $scss);
		$this->assertStringContainsString("src:url('../font/font3/font3-v3-latin-300italic.woff2')", $scss);
		// 700 does not exist for font3, so it should not be included
		$this->assertStringNotContainsString("src:url('../font/font3/font3-v3-latin-700.woff2')", $scss);

		$this->assertSame(7, substr_count($scss, '@font-face'));
		$this->assertSame(7, substr_count($scss, 'font-family:'));

		$this->assertSame(4, substr_count($scss, 'font-style:normal'));
		$this->assertSame(3, substr_count($scss, 'font-style:italic'));

		$this->assertSame(2, substr_count($scss, 'font-weight:700'));
		$this->assertSame(4, substr_count($scss, 'font-weight:400'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::buildFontFaceBlock()
	 * @throws \ReflectionException
	 */
	public function testBuildFontFaceBlock(): void {
		$task = new WebfontDownloadTask();
		$template = $this->callProtectedMethod(
			$task,
			'buildFontFaceBlock',
			'font123',
			'My Font',
			'italic',
			700,
			'font123.woff2'
		);

		$this->assertStringContainsString('@font-face {', $template);
		$this->assertStringContainsString("font-family:'My Font';", $template);
		$this->assertStringContainsString('font-style:italic;', $template);
		$this->assertStringContainsString('font-weight:700;', $template);
		$this->assertStringContainsString("src:url('../font/font123/font123.woff2')", $template);
	}


	/**
	 * @return void;
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::getDownloadUrl()
	 * @throws \ReflectionException
	 */
	public function testGetDownloadUrl(): void {
		$task = new WebfontDownloadTask();
		$url = $this->callProtectedMethod(
			$task,
			'getDownloadUrl',
			['id' => 'font_1', 'name' => 'Test Font', 'version' => 'v10', 'variants' => ['regular', '400', '400i', 'italic', '700', '700i', 400]]
		);

		$this->assertSame('https://gwfh.mranftl.com/api/fonts/font_1?download=zip&subsets=latin%2Clatin-ext&formats=woff2&variants=regular%2C400%2C400i%2Citalic%2C700%2C700i', $url);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::run()
	 */
	public function testRunWithNoFonts(): void {
		$scssPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'webfonts.scss';
		if (file_exists($scssPath)) {
			unlink($scssPath);
		}

		$task = $this->getMockBuilder(WebfontDownloadTask::class)->onlyMethods(['getDownloadUrl', 'generateScssFile'])->getMock();
		// Neither getDownloadUrl nor generateScssFile should be called
		$task->expects($this->never())->method('getDownloadUrl');
		$task->expects($this->never())->method('generateScssFile');

		$task->run(['fonts' => []], 1);
		$this->assertFileDoesNotExist($scssPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::run()
	 */
	public function testRunCorrectlyBuildsFontData(): void {
		$task = $this->getMockBuilder(WebfontDownloadTask::class)
			->onlyMethods(['getDownloadUrl', 'generateScssFile'])
			->getMock();

		// Create a temporary zip with one font file
		$zipPath = TMP . DS . 'testfont.zip';
		$zip = new ZipArchive();
		$zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);
		$zip->addFromString('font1-v1-latin-regular.woff2', 'dummy');
		$zip->close();

		// Mock the getDownloadUrl method to return a dummy URL
		$task->expects($this->once())->method('getDownloadUrl')->willReturnCallback(function (array $data) use ($zipPath): string {
			// Assert that the run method built the font variants correctly
			$this->assertSame([
				'id' => 'font_1',
				'name' => 'Test Font',
				'version' => 'v10',
				'variants' => [
					'regular',
					'regular',
					'italic',
					'italic',
					'700',
					'700italic',
				],
			], $data);

			return 'file://' . $zipPath;
		});

		// Mock the generateScssFile method to return a dummy SCSS content
		$task->method('generateScssFile')->willReturn('');

		$data = [
			'fonts' => [
				['id' => 'font_1', 'name' => 'Test Font', 'version' => 'v10', 'variants' => ['regular', '400', '400i', 'italic', '700', '700i']],
			],
		];

		$task->run($data, 1);

		// Remove the temporary zip file
		unlink($zipPath);
	}



	/**
	 * @return void
	 * @see \Awyiss\Queue\Task\Design\WebfontDownloadTask::run()
	 */
	public function testRunDownloadsAndGeneratesScss(): void {
		// Create a temporary zip with one font file
		$zipPath = TMP . DS . 'testfont.zip';
		$zip = new ZipArchive();
		$zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);
		$zip->addFromString('font1-v1-latin-regular.woff2', 'dummy');
		$zip->addFromString('font1-v1-latin-italic.woff2', 'dummy');
		$zip->addFromString('font1-v1-latin-700.woff2', 'dummy');
		$zip->addFromString('font2-v2-latin_latin-ext-700.woff2', 'dummy');
		$zip->addFromString('font3-v30-latin-italic.woff2', 'dummy');
		$zip->addFromString('font3-v30-latin-700italic.woff2', 'dummy');
		$zip->close();

		// Mock the getDownloadUrl method to return our temporary zip
		$task = $this->getStubBuilder(WebfontDownloadTask::class)
			->onlyMethods(['getDownloadUrl'])
			->getStub();
		$task->method('getDownloadUrl')->willReturn('file://' . $zipPath);

		// Prepare data
		$data = [
			'fonts' => [
				['id' => 'font1', 'name' => 'Test Font', 'version' => 'v1', 'variants' => ['regular', 'italic', '700']],
				['id' => 'font2', 'name' => 'Another Font', 'version' => 'v2', 'variants' => ['700']],
				['id' => 'font3', 'name' => 'Third Font', 'version' => 'v30', 'variants' => ['400i', '700i']],
			],
		];

		$fontPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'font' . DS;

		// Cleanup previous output
		$this->rmdir();

		$scssPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'webfonts.scss';
		if (file_exists($scssPath)) {
			unlink($scssPath);
		}

		// Run task
		$task->run($data, 1);

		// Remove the temporary zip file
		unlink($zipPath);

		// Assertions
		$this->assertFileExists($fontPath . 'font1' . DS . 'font1-v1-latin-regular.woff2');
		$this->assertFileExists($fontPath . 'font1' . DS . 'font1-v1-latin-italic.woff2');
		$this->assertFileExists($fontPath . 'font1' . DS . 'font1-v1-latin-700.woff2');
		$this->assertFileExists($fontPath . 'font2' . DS . 'font2-v2-latin_latin-ext-700.woff2');
		$this->assertFileExists($fontPath . 'font3' . DS . 'font3-v30-latin-italic.woff2');
		$this->assertFileExists($fontPath . 'font3' . DS . 'font3-v30-latin-700italic.woff2');

		$this->assertFileExists($scssPath);

		$scss = file_get_contents($scssPath);
		$this->assertSame(6, substr_count($scss, '@font-face'));
		$this->assertStringContainsString("url('../font/font1/font1-v1-latin-regular.woff2')", $scss);
		$this->assertStringContainsString("url('../font/font1/font1-v1-latin-italic.woff2')", $scss);
		$this->assertStringContainsString("url('../font/font1/font1-v1-latin-700.woff2')", $scss);
		$this->assertStringContainsString("url('../font/font2/font2-v2-latin_latin-ext-700.woff2')", $scss);
		$this->assertStringContainsString("url('../font/font3/font3-v30-latin-italic.woff2')", $scss);
		$this->assertStringContainsString("url('../font/font3/font3-v30-latin-700italic.woff2')", $scss);

		// All three downloaded zips should not be in the font directory
		$this->assertFileDoesNotExist($fontPath . 'font1.zip');
		$this->assertFileDoesNotExist($fontPath . 'font2.zip');
		$this->assertFileDoesNotExist($fontPath . 'font3.zip');
	}


	/**
	 * @return void
	 */
	protected function rmdir(): void {
		new Process(['rm', '-rf', ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'font'])->run();
	}
}
