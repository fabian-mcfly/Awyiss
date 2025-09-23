<?php
/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore



namespace Awyiss\Test\TestCase\Utility\Design;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Design\ScssVariableProvider;
use Awyiss\Utility\Design\ScssVariableType;
use Cake\Core\Configure;
use ScssPhp\ScssPhp\Ast\Sass\Expression\BooleanExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\ColorExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\FunctionExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\ListExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\NumberExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\StringExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\VariableExpression;


/**
 * Test case for ScssVariableProvider
 *
 * @see \Awyiss\Utility\Design\ScssVariableProvider
 */
class ScssVariableProviderTest extends TestCase {
	protected array $designConfig;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->designConfig = Configure::read('Design', []);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructor(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$this->assertSame(['includeColumnSystem'], $scssVariableProvider->getConfig('blocklistedVariables'));
		$this->assertSame([ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . 'test.scss'], $scssVariableProvider->getConfig('previewScssFiles'));

		$this->assertSame([ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables.scss'], $scssVariableProvider->getScssFiles());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::addScssFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddScssFiles(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$this->assertCount(1, $scssVariableProvider->getScssFiles());

		$scssVariableProvider->addScssFile(ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables2.scss');

		$this->assertCount(2, $scssVariableProvider->getScssFiles());
		$this->assertSame([
			ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables.scss',
			ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables2.scss',
		], $scssVariableProvider->getScssFiles());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::setScssFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetScssFiles(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$scssVariableProvider->setScssFiles([ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables2.scss']);

		$this->assertCount(1, $scssVariableProvider->getScssFiles());
		$this->assertSame([ROOT . DS . 'tests' . DS . 'customer' . DS . 'assets' . DS . 'scss' . DS . '_variables2.scss'], $scssVariableProvider->getScssFiles());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::getInternalVariables()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetInternalVariables(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$internalVariables = $scssVariableProvider->getInternalVariables();

		$this->assertSame([
			'includeColumnSystem',
			'fontNameMain',
			'fontStackFallbackMain',
			'fontWeightMain',
			'fontStyleMain',
			'fontSizeMain',
			'lineHeightMain',
			'fontNameAlternative',
			'fontStackFallbackAlternative',
			'fontWeightAlternative',
			'fontStyleAlternative',
			'fontSizeAlternative',
			'lineHeightAlternative',
			'colorText',
			'colorDark',
			'colorMedium',
			'colorLight',
			'colorBright',
			'colorMain',
			'colorContrast',
			'pageWidth',
			'pagePadding',
			'columnMargin',
			'menuBreakpoint',
			'singleColumnBreakpoint',
			'exampleFunction',
			'exampleVariable',
		], array_keys($internalVariables));

		$this->assertInstanceOf(BooleanExpression::class, $internalVariables['includeColumnSystem']);
		$this->assertInstanceOf(StringExpression::class, $internalVariables['fontNameMain']);
		$this->assertInstanceOf(ListExpression::class, $internalVariables['fontStackFallbackMain']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['fontWeightMain']);
		$this->assertInstanceOf(StringExpression::class, $internalVariables['fontStyleMain']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['fontSizeMain']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['lineHeightMain']);
		$this->assertInstanceOf(StringExpression::class, $internalVariables['fontNameAlternative']);
		$this->assertInstanceOf(ListExpression::class, $internalVariables['fontStackFallbackAlternative']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['fontWeightAlternative']);
		$this->assertInstanceOf(StringExpression::class, $internalVariables['fontStyleAlternative']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['fontSizeAlternative']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['lineHeightAlternative']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorText']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorDark']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorMedium']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorLight']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorBright']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorMain']);
		$this->assertInstanceOf(ColorExpression::class, $internalVariables['colorContrast']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['pageWidth']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['pagePadding']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['columnMargin']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['menuBreakpoint']);
		$this->assertInstanceOf(NumberExpression::class, $internalVariables['singleColumnBreakpoint']);
		$this->assertInstanceOf(FunctionExpression::class, $internalVariables['exampleFunction']);
		$this->assertInstanceOf(VariableExpression::class, $internalVariables['exampleVariable']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::getNormalizedInternalVariables()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNormalizedInternalVariables(): void {
		$scssVariableProvider = $this->getMockBuilder(ScssVariableProvider::class)->setConstructorArgs([$this->designConfig])->onlyMethods([
			'normalizeValue',
			'variableIsBlocklisted',
			'mergeOptions',
		])->getMock();
		$scssVariableProvider->expects($this->exactly(27))->method('variableIsBlocklisted')->willReturn(false);
		$scssVariableProvider->expects($this->exactly(27))->method('normalizeValue')->willReturn([]);
		$scssVariableProvider->expects($this->exactly(27))->method('mergeOptions')->willReturn([]);

		$internalVariables = $scssVariableProvider->getNormalizedInternalVariables();

		$this->assertSame([
			'includeColumnSystem',
			'fontNameMain',
			'fontStackFallbackMain',
			'fontWeightMain',
			'fontStyleMain',
			'fontSizeMain',
			'lineHeightMain',
			'fontNameAlternative',
			'fontStackFallbackAlternative',
			'fontWeightAlternative',
			'fontStyleAlternative',
			'fontSizeAlternative',
			'lineHeightAlternative',
			'colorText',
			'colorDark',
			'colorMedium',
			'colorLight',
			'colorBright',
			'colorMain',
			'colorContrast',
			'pageWidth',
			'pagePadding',
			'columnMargin',
			'menuBreakpoint',
			'singleColumnBreakpoint',
			'exampleFunction',
			'exampleVariable',
		], array_keys($internalVariables));


		$scssVariableProvider = new ScssVariableProvider($this->designConfig);
		$internalVariables = $scssVariableProvider->getNormalizedInternalVariables();

		$this->assertArrayNotHasKey('includeColumnSystem', $internalVariables);

		// Test for fontNameMain, pageWidth and colorMain
		$this->assertSame([
			'associatedVariables' => [
				'fontStackFallbackMain',
				'fontStackMain',
				'fontWeightMain',
				'fontStyleMain',
				'fontSizeMain',
				'lineHeightMain',
				'fontSizeClampMain',
			],
			'category' => 'fonts',
			'type' => ScssVariableType::FontName,
			'unit' => null,
			'quotes' => '\'',
			'value' => 'Comic Sans MS',
			'units' => null,
		], $internalVariables['fontNameMain']);

		$this->assertSame([
			'forcedUnit' => 'px',
			'inputType' => 'range',
			'stripUnit' => true,
			'type' => ScssVariableType::Number,
			'units' => [
				'px' => [
					'range' => [
						'min' => 320,
						'max' => 3840,
					],
					'step' => 1,
				],
			],
			'unit' => 'px',
			'quotes' => null,
			'value' => 1280.0,
		], $internalVariables['pageWidth']);

		$this->assertSame([
			'category' => 'colors',
			'type' => ScssVariableType::Color,
			'unit' => null,
			'quotes' => null,
			'value' => '#17bbe1',
			'units' => null,
		], $internalVariables['colorMain']);
	}


	/**
	 * Test normalizeValue for BooleanExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForBooleanExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['includeColumnSystem'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => null,
			'quotes' => null,
			'value' => true,
		], $normalized);
	}


	/**
	 * Test normalizeValue for ColorExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForColorExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['colorText'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => null,
			'quotes' => null,
			'value' => '#043a4f',
		], $normalized);
	}


	/**
	 * Test normalizeValue for NumberExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForNumberExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontSizeMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => 'px',
			'quotes' => null,
			'value' => 18.0,
		], $normalized);
	}


	/**
	 * Test normalizeValue for StringExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForStringExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontNameMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => null,
			'quotes' => '\'',
			'value' => 'Comic Sans MS',
		], $normalized);
	}


	/**
	 * Test normalizeValue for ListExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForListExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontStackFallbackMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => null,
			'quotes' => null,
			'value' => 'Arial,Geneva,sans-serif',
		], $normalized);
	}


	/**
	 * Test normalizeValue for FunctionExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForFunctionExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['exampleFunction'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => null,
			'quotes' => null,
			'value' => 'linear-gradient(45deg, red, yellow, blue)',
		], $normalized);
	}


	/**
	 * Test normalizeValue for VariableExpression
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNormalizeValueForVariableExpression(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['exampleVariable'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);

		$this->assertSame([
			'unit' => null,
			'quotes' => null,
			'value' => '$pageWidth',
		], $normalized);
	}



	/**
	 * Test mergeOptions for a variable `fontNameMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForFontName(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontNameMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'fontNameMain', $normalized);

		$this->assertSame([
			'associatedVariables' => [
				'fontStackFallbackMain',
				'fontStackMain',
				'fontWeightMain',
				'fontStyleMain',
				'fontSizeMain',
				'lineHeightMain',
				'fontSizeClampMain',
			],
			'category' => 'fonts',
			'type' => ScssVariableType::FontName,
			'unit' => null,
			'quotes' => '\'',
			'value' => 'Comic Sans MS',
			'units' => null,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `fontStackFallbackMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForFontStackFallback(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontStackFallbackMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'fontStackFallbackMain', $normalized);

		$this->assertSame([
			'category' => 'fonts',
			'type' => ScssVariableType::FontStack,
			'unit' => null,
			'quotes' => null,
			'value' => 'Arial,Geneva,sans-serif',
			'units' => null,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `fontSizeMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForFontSize(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontSizeMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'fontSizeMain', $normalized);

		$this->assertSame([
			'category' => 'fonts',
			'inputType' => 'range',
			'type' => ScssVariableType::Number,
			'units' => [
				'px' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 1,
				],
				'rem' => [
					'range' => [
						'min' => 0,
						'max' => 20,
					],
					'step' => 0.001,
				],
				'vw' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 0.01,
				],
				'%' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 0.01,
				],
				'em' => [
					'range' => [
						'min' => 0,
						'max' => 20,
					],
					'step' => 0.001,
				],
			],
			'unit' => 'px',
			'quotes' => null,
			'value' => 18.0,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `fontStyleMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForFontStyle(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontStyleMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'fontStyleMain', $normalized);

		$this->assertSame([
			'category' => 'fonts',
			'options' => [
				'normal',
				'italic',
			],
			'type' => ScssVariableType::String,
			'unit' => null,
			'quotes' => '\'',
			'value' => 'normal',
			'units' => null,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `fontWeightMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForFontWeight(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['fontWeightMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'fontWeightMain', $normalized);

		$this->assertSame([
			'category' => 'fonts',
			'type' => ScssVariableType::FontWeight,
			'unit' => null,
			'quotes' => null,
			'value' => 400.0,
			'units' => null,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `lineHeightMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForLineHeight(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['lineHeightMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'lineHeightMain', $normalized);

		$this->assertSame([
			'category' => 'fonts',
			'inputType' => 'range',
			'type' => ScssVariableType::Number,
			'units' => [
				'px' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 1,
				],
				'rem' => [
					'range' => [
						'min' => 0,
						'max' => 10,
					],
					'step' => 0.001,
				],
				'vw' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 0.01,
				],
				'%' => [
					'range' => [
						'min' => 0,
						'max' => 1000,
					],
					'step' => 1,
				],
				'' => [
					'range' => [
						'min' => 0,
						'max' => 10,
					],
					'step' => 0.01,
				],
			],
			'unit' => 'rem',
			'quotes' => null,
			'value' => 1.625,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `colorMain`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForColor(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['colorMain'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'colorMain', $normalized);

		$this->assertSame([
			'category' => 'colors',
			'type' => ScssVariableType::Color,
			'unit' => null,
			'quotes' => null,
			'value' => '#17bbe1',
			'units' => null,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `pageWidth`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForWidth(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['pageWidth'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'pageWidth', $normalized);

		$this->assertSame([
			'forcedUnit' => 'px',
			'inputType' => 'range',
			'stripUnit' => true,
			'type' => ScssVariableType::Number,
			'units' => [
				'px' => [
					'range' => [
						'min' => 320,
						'max' => 3840,
					],
					'step' => 1,
				],
			],
			'unit' => 'px',
			'quotes' => null,
			'value' => 1280.0,
		], $mergedOptions);
	}


	/**
	 * Test mergeOptions for a variable `columnMargin`
	 *
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::normalizeValue()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMergeOptionsForMargin(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$variable = $scssVariableProvider->getInternalVariables()['columnMargin'];
		$normalized = $this->callProtectedMethod($scssVariableProvider, 'normalizeValue', $variable);
		$mergedOptions = $this->callProtectedMethod($scssVariableProvider, 'mergeOptions', 'columnMargin', $normalized);

		$this->assertSame([
			'inputType' => 'range',
			'type' => ScssVariableType::Number,
			'unit' => '%',
			'quotes' => null,
			'value' => 3.0,
			'units' => [
				'px' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 1,
				],
				'rem' => [
					'range' => [
						'min' => 0,
						'max' => 10,
					],
					'step' => 0.001,
				],
				'vw' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 0.01,
				],
				'%' => [
					'range' => [
						'min' => 0,
						'max' => 100,
					],
					'step' => 0.01,
				],
			],
		], $mergedOptions);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::variableIsBlocklisted()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVariableIsBlocklistedExactMatch(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$notBlocked = $this->callProtectedMethod($scssVariableProvider, 'variableIsBlocklisted', 'blockedVariable');
		$this->assertFalse($notBlocked, 'Variable should not be blocklisted');

		$scssVariableProvider->setConfig('blocklistedVariables', ['blockedVariable']);
		$blocked = $this->callProtectedMethod($scssVariableProvider, 'variableIsBlocklisted', 'blockedVariable');
		$this->assertTrue($blocked, 'Variable should be blocklisted');
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssVariableProvider::variableIsBlocklisted()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVariableIsBlocklistedRegexMatch(): void {
		$scssVariableProvider = new ScssVariableProvider($this->designConfig);

		$notBlocked = $this->callProtectedMethod($scssVariableProvider, 'variableIsBlocklisted', 'notBlockedVariable');
		$this->assertFalse($notBlocked, 'Variable should not be blocklisted');

		$scssVariableProvider->setConfig('blocklistedVariables', ['blocked.*']);
		$blocked = $this->callProtectedMethod($scssVariableProvider, 'variableIsBlocklisted', 'blockedVariable');
		$this->assertTrue($blocked, 'Variable should be blocklisted');
	}
}
