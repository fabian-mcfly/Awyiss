<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Media;


use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;


/**
 * Test case for MediaRenderOptions class
 *
 * @see \Awyiss\Utility\Media\MediaRenderOptions
 */
class MediaRenderOptionsTest extends TestCase {
	/**
	 * Test default values from constructor
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDefaultValues(): void {
		$options = new MediaRenderOptions();

		$this->assertFalse($options->getAllowUpscale());
		$this->assertNull($options->getAspectRatio());
		$this->assertEmpty($options->getAttributes());
		$this->assertNull($options->getBackgroundColor());
		$this->assertEquals(3840.0, $options->getBaseWidth());
		$this->assertIsArray($options->getBreakpoints());
		$this->assertEquals(100.0, $options->getColumnWidth());
		$this->assertNull($options->getHeight());
		$this->assertTrue($options->getInclude2x());
		$this->assertNull($options->getMinBreakpoint());
		$this->assertEquals(ResizeStrategy::Contain, $options->getResizeStrategy());
		$this->assertTrue($options->getResponsive());
		$this->assertNull($options->getSelector());
		$this->assertNull($options->getSingleColumnBreakpoint());
		$this->assertFalse($options->getStrictSize());
		$this->assertNull($options->getWidth());
	}


	/**
	 * Test withAllowUpscale method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithAllowUpscale(): void {
		$options = new MediaRenderOptions();

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$newOptions = $options->withAllowUpscale(true);
		$this->assertTrue($newOptions->getAllowUpscale());

		// Make sure the original was not modified
		$this->assertFalse($options->getAllowUpscale(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withAllowUpscale(false);
		$this->assertFalse($newOptions->getAllowUpscale());
	}


	/**
	 * Test withAspectRatio method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithAspectRatio(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withAspectRatio(1.5);
		$this->assertEquals(1.5, $newOptions->getAspectRatio());

		// Make sure the original was not modified
		$this->assertNull($options->getAspectRatio(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withAspectRatio(null);
		$this->assertNull($newOptions->getAspectRatio());

		// Test int to float conversion
		$newOptions = $options->withAspectRatio(2);
		$this->assertEquals(2.0, $newOptions->getAspectRatio());
		$this->assertIsFloat($newOptions->getAspectRatio());
	}


	/**
	 * Test withAttributes method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithAttributes(): void {
		$options = new MediaRenderOptions();

		$attributes = ['class' => 'img-fluid', 'alt' => 'Test image'];
		$newOptions = $options->withAttributes($attributes);
		$this->assertEquals($attributes, $newOptions->getAttributes());

		// Make sure the original was not modified
		$this->assertEmpty($options->getAttributes(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		// Test that array is deep copied
		$attributes['class'] = 'modified';
		$this->assertNotEquals($attributes, $newOptions->getAttributes());
	}


	/**
	 * Test withBackgroundColor method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithBackgroundColor(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withBackgroundColor('#ffffff');
		$this->assertEquals('#ffffff', $newOptions->getBackgroundColor());

		// Make sure the original was not modified
		$this->assertNull($options->getBackgroundColor(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withBackgroundColor(null);
		$this->assertNull($newOptions->getBackgroundColor());

		$newOptions = $options->withBackgroundColor(false);
		$this->assertFalse($newOptions->getBackgroundColor());
	}


	/**
	 * Test withBaseWidth method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithBaseWidth(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withBaseWidth(1280);
		$this->assertEquals(1280.0, $newOptions->getBaseWidth());
		$this->assertIsFloat($newOptions->getBaseWidth());

		// Make sure the original was not modified
		$this->assertEquals(3840.0, $options->getBaseWidth(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withBaseWidth(1280.5);
		$this->assertEquals(1280.5, $newOptions->getBaseWidth());
	}


	/**
	 * Test withBreakpoint method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithBreakpoint(): void {
		$options = new MediaRenderOptions();

		$this->assertEmpty($options->getBreakpoints(), 'Initial breakpoints should be empty');

		$newOptions = $options->withBreakpoint(768, ['width' => 600]);
		$breakpoints = $newOptions->getBreakpoints();

		$this->assertNotEmpty($breakpoints);

		// Find the breakpoint in the array
		$foundBreakpoint = false;
		foreach ($breakpoints as $breakpoint) {
			if ($breakpoint['breakpoint'] === 768.0) {
				$this->assertEquals(600.0, $breakpoint['width']);
				$foundBreakpoint = true;
				break;
			}
		}

		$this->assertTrue($foundBreakpoint, 'Breakpoint should be found in the array');

		// Make sure the original was not modified
		$this->assertEmpty($options->getBreakpoints(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);
	}


	/**
	 * Test withBreakpoints method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithBreakpoints(): void {
		$options = new MediaRenderOptions();

		// Make sure the original was not modified
		$this->assertEmpty($options->getBreakpoints(), 'Initial breakpoints should be empty');

		$breakpoints = [
			1200 => ['width' => 1140],
			992 => ['width' => 960],
			768 => ['width' => 720],
			576 => ['width' => 540],
		];

		$newOptions = $options->withBreakpoints($breakpoints);
		$normalizedBreakpoints = $newOptions->getBreakpoints();

		$this->assertCount(4, $normalizedBreakpoints);

		// Check that breakpoints are sorted
		$lastBreakpoint = 0;
		foreach ($normalizedBreakpoints as $breakpoint) {
			$this->assertGreaterThan($lastBreakpoint, $breakpoint['breakpoint']);
			$lastBreakpoint = $breakpoint['breakpoint'];
		}

		// Make sure the original was not modified
		$this->assertEmpty($options->getBreakpoints(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);
	}


	/**
	 * Test withColumnWidth method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithColumnWidth(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withColumnWidth(120);
		$this->assertEquals(120.0, $newOptions->getColumnWidth());
		$this->assertIsFloat($newOptions->getColumnWidth());

		// Make sure the original was not modified
		$this->assertEquals(100.0, $options->getColumnWidth(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);
	}


	/**
	 * Test withHeight method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithHeight(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withHeight(500);
		$this->assertEquals(500.0, $newOptions->getHeight());
		$this->assertIsFloat($newOptions->getHeight());

		$newOptions = $options->withHeight(null);
		$this->assertNull($newOptions->getHeight());

		// Make sure the original was not modified
		$this->assertNull($options->getHeight(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);
	}


	/**
	 * Test withInclude2x method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithInclude2x(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withInclude2x(false);
		$this->assertFalse($newOptions->getInclude2x());

		// Make sure the original was not modified
		$this->assertTrue($options->getInclude2x(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withInclude2x(true);
		$this->assertTrue($newOptions->getInclude2x());
	}


	/**
	 * Test withMinBreakpoint method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithMinBreakpoint(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withMinBreakpoint(320);
		$this->assertEquals(320.0, $newOptions->getMinBreakpoint());

		// Make sure the original was not modified
		$this->assertNull($options->getMinBreakpoint(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withMinBreakpoint(null);
		$this->assertNull($newOptions->getMinBreakpoint());
	}


	/**
	 * Test withResizeStrategy method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithResizeStrategy(): void {
		$options = new MediaRenderOptions();

		// Test with enum value
		$newOptions = $options->withResizeStrategy(ResizeStrategy::Crop);
		$this->assertEquals(ResizeStrategy::Crop, $newOptions->getResizeStrategy());

		// Make sure the original was not modified
		$this->assertEquals(ResizeStrategy::Contain, $options->getResizeStrategy(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		// Test with string value
		$newOptions = $options->withResizeStrategy('Contain');
		$this->assertEquals(ResizeStrategy::Contain, $newOptions->getResizeStrategy());
	}


	/**
	 * Test withResponsive method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithResponsive(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withResponsive(false);
		$this->assertFalse($newOptions->getResponsive());

		// Make sure the original was not modified
		$this->assertTrue($options->getResponsive(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$newOptions = $options->withResponsive(true);
		$this->assertTrue($newOptions->getResponsive());
	}


	/**
	 * Test withSelector method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithSelector(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withSelector('.image-container');
		$this->assertEquals('.image-container', $newOptions->getSelector());

		// Make sure the original was not modified
		$this->assertNull($options->getSelector(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withSelector(null);
		$this->assertNull($newOptions->getSelector());
	}


	/**
	 * Test withSingleColumnBreakpoint method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithSingleColumnBreakpoint(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withSingleColumnBreakpoint(576);
		$this->assertEquals(576.0, $newOptions->getSingleColumnBreakpoint());

		// Make sure the original was not modified
		$this->assertNull($options->getSingleColumnBreakpoint(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withSingleColumnBreakpoint(null);
		$this->assertNull($newOptions->getSingleColumnBreakpoint());

		$newOptions = $options->withSingleColumnBreakpoint(false);
		$this->assertFalse($newOptions->getSingleColumnBreakpoint());
	}


	/**
	 * Test withStrictSize method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithStrictSize(): void {
		$options = new MediaRenderOptions();

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$newOptions = $options->withStrictSize(true);
		$this->assertTrue($newOptions->getStrictSize());

		// Make sure the original was not modified
		$this->assertFalse($options->getStrictSize(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withStrictSize(false);
		$this->assertFalse($newOptions->getStrictSize());
	}


	/**
	 * Test withWidth method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithWidth(): void {
		$options = new MediaRenderOptions();

		$newOptions = $options->withWidth(800);
		$this->assertEquals(800.0, $newOptions->getWidth());
		$this->assertIsFloat($newOptions->getWidth());

		// Make sure the original was not modified
		$this->assertNull($options->getWidth(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options);

		$newOptions = $options->withWidth(null);
		$this->assertNull($newOptions->getWidth());
	}


	/**
	 * Test with method for multiple property changes
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithMultipleChanges(): void {
		$options = new MediaRenderOptions();

		// Make sure the original was not modified
		$this->assertNull($options->getWidth());
		$this->assertNull($options->getHeight());

		$newOptions = $options->with([
			'width' => 800,
			'height' => 600,
			'resizeStrategy' => ResizeStrategy::Crop,
			'allowUpscale' => true,
		]);

		$this->assertEquals(800.0, $newOptions->getWidth());
		$this->assertEquals(600.0, $newOptions->getHeight());
		$this->assertEquals(ResizeStrategy::Crop, $newOptions->getResizeStrategy());
		$this->assertTrue($newOptions->getAllowUpscale());

		// Make sure the original was not modified
		$this->assertNull($options->getWidth(), 'Original options should not be modified');
		$this->assertNull($options->getHeight(), 'Original options should not be modified');
		$this->assertEquals(ResizeStrategy::Contain, $options->getResizeStrategy(), 'Original options should not be modified');
		$this->assertFalse($options->getAllowUpscale(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options, 'New options should be a different instance');
	}


	/**
	 * Test with PRESERVE_VALUE constant
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWithPreserveValue(): void {
		$options = new MediaRenderOptions(width: 500, height: 300);

		// Make sure the original was not modified
		$this->assertSame(500.0, $options->getWidth());
		$this->assertSame(300.0, $options->getHeight());

		$newOptions = $options->with([
			'width' => MediaRenderOptions::PRESERVE_VALUE,
			'height' => 400,
		]);

		$this->assertEquals(500.0, $newOptions->getWidth(), 'Width should remain unchanged');
		$this->assertEquals(400.0, $newOptions->getHeight(), 'Height should be updated');

		// Make sure the original was not modified
		$this->assertSame(500.0, $options->getWidth(), 'Original options should not be modified');
		$this->assertSame(300.0, $options->getHeight(), 'Original options should not be modified');
		$this->assertNotSame($newOptions, $options, 'New options should be a different instance');
	}


	/**
	 * Test normalizeBreakpoint method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeBreakpoint(): void {
		// Test with numeric value
		$result = MediaRenderOptions::normalizeBreakpoint(768, 600);
		$this->assertEquals(600.0, $result['breakpoint']);
		$this->assertEquals(MediaRenderOptions::PRESERVE_VALUE, $result['width']);
		$this->assertNull($result['baseWidth']);

		// Test with array value
		$result = MediaRenderOptions::normalizeBreakpoint(768, [
			'width' => 600,
			'height' => 400,
			'resizeStrategy' => ResizeStrategy::Crop,
		]);

		$this->assertEquals(768.0, $result['breakpoint']);
		$this->assertEquals(600.0, $result['width']);
		$this->assertEquals(400.0, $result['height']);
		$this->assertEquals(ResizeStrategy::Crop, $result['resizeStrategy']);
	}


	/**
	 * Test normalizeBreakpoints method
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeBreakpoints(): void {
		$breakpoints = [
			576 => 540,
			768 => ['width' => 720],
			992 => ['width' => 960, 'height' => 300],
			1200 => ['width' => 1140, 'resizeStrategy' => ResizeStrategy::Crop],
		];

		$result = MediaRenderOptions::normalizeBreakpoints($breakpoints);

		// Check we have all breakpoints
		$this->assertCount(4, $result);

		$this->assertIsList($result);

		$this->assertEquals(MediaRenderOptions::PRESERVE_VALUE, $result[0]['width']);
		$this->assertEquals(540, $result[0]['breakpoint']);

		$this->assertEquals(720.0, $result[1]['width']);
		$this->assertEquals(768.0, $result[1]['breakpoint']);

		$this->assertEquals(960.0, $result[2]['width']);
		$this->assertEquals(300.0, $result[2]['height']);

		$this->assertEquals(ResizeStrategy::Crop, $result[3]['resizeStrategy']);
	}


	/**
	 * Test deepCopy method through attributes
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDeepCopy(): void {
		$options = new MediaRenderOptions(attributes: [
			'class' => 'image',
			'nested' => ['value1', 'value2'],
		]);

		$attributes = $options->getAttributes();
		$attributes['class'] = 'video';
		$attributes['nested'][0] = 'value3';

		$newOptions = $options->withAttributes($attributes);

		// Verify new options have modified attributes
		$this->assertEquals('video', $newOptions->getAttributes()['class']);
		$this->assertEquals('value3', $newOptions->getAttributes()['nested'][0]);

		// Verify original attributes haven't changed
		$this->assertEquals('image', $options->getAttributes()['class']);
		$this->assertEquals('value1', $options->getAttributes()['nested'][0]);
	}
}
