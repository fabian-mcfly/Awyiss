<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Twig\Extension;


use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Twig\Extension\EnumExtension;
use BadMethodCallException;
use InvalidArgumentException;
use Twig\TwigFunction;
use Twig\TwigTest;


/**
 * Test case for EnumExtension
 *
 * @see \Awyiss\Twig\Extension\EnumExtension
 */
class EnumExtensionTest extends TestCase {
	/**
	 * @var \Awyiss\Twig\Extension\EnumExtension
	 */
	protected EnumExtension $extension;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->extension = new EnumExtension();
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getFunctions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFunctionsReturnsArrayWithEnumFunction(): void {
		$functions = $this->extension->getFunctions();

		$this->assertIsArray($functions);
		$this->assertCount(1, $functions);
		$this->assertInstanceOf(TwigFunction::class, $functions[0]);
		$this->assertEquals('enum', $functions[0]->getName());
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getFunctions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnumFunctionReturnsProxyForValidEnum(): void {
		$functions = $this->extension->getFunctions();
		$enumFunction = $functions[0];
		$callable = $enumFunction->getCallable();

		$proxy = $callable(ResizeStrategy::class);

		$this->assertIsObject($proxy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getFunctions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnumFunctionForCase(): void {
		$functions = $this->extension->getFunctions();
		$enumFunction = $functions[0];
		$callable = $enumFunction->getCallable();

		$enum = $callable(ResizeStrategy::class);
		$result = $enum->Cover();

		$this->assertSame(ResizeStrategy::Cover, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getFunctions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnumFunctionForMethod(): void {
		$functions = $this->extension->getFunctions();
		$enumFunction = $functions[0];
		$callable = $enumFunction->getCallable();

		$enum = $callable(ResizeStrategy::class);
		$result = $enum->normalize('crop');

		$this->assertSame(ResizeStrategy::Crop, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getFunctions()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnumFunctionForUnknownMethodThrowsException(): void {
		$functions = $this->extension->getFunctions();
		$enumFunction = $functions[0];
		$callable = $enumFunction->getCallable();

		$enum = $callable(ResizeStrategy::class);

		$this->expectException(BadMethodCallException::class);
		$this->expectExceptionMessage('Case or method `unknownMethod` does not exist in `Awyiss\Model\Enum\ResizeStrategy`');

		$enum->unknownMethod();
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getTests()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTestsReturnsArrayWithEnumTest(): void {
		$tests = $this->extension->getTests();

		$this->assertIsArray($tests);
		$this->assertCount(1, $tests);
		$this->assertInstanceOf(TwigTest::class, $tests[0]);
		$this->assertEquals('enum', $tests[0]->getName());
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getTests()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnumTestReturnsTrueForBackedEnum(): void {
		$tests = $this->extension->getTests();
		$enumTest = $tests[0];
		$callable = $enumTest->getCallable();

		$testEnum = ResizeStrategy::Crop;
		$result = $callable($testEnum);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::getTests()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEnumTestReturnsFalseForNonEnum(): void {
		$tests = $this->extension->getTests();
		$enumTest = $tests[0];
		$callable = $enumTest->getCallable();

		$result = $callable('not an enum');

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::createProxy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateProxyReturnsObjectForValidEnum(): void {
		$proxy = $this->extension->createProxy(ResizeStrategy::class);

		$this->assertIsObject($proxy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::createProxy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateProxyThrowsExceptionForInvalidEnum(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('`NonExistentEnum` is not an Enum type and cannot be used in this function');

		$this->extension->createProxy('NonExistentEnum');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::createProxy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProxyCanAccessEnumCases(): void {
		$proxy = $this->extension->createProxy(ResizeStrategy::class);

		$result = $proxy->Contain();

		$this->assertEquals(ResizeStrategy::Contain, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::createProxy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProxyCanAccessEnumMethods(): void {
		$proxy = $this->extension->createProxy(ResizeStrategy::class);

		$result = $proxy->normalize('contain');

		$this->assertEquals(ResizeStrategy::Contain, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\EnumExtension::createProxy()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProxyThrowsExceptionForNonExistentConstantOrMethod(): void {
		$proxy = $this->extension->createProxy(ResizeStrategy::class);

		$this->expectException(BadMethodCallException::class);
		$this->expectExceptionMessage('Case or method `unknownMethod` does not exist in `Awyiss\Model\Enum\ResizeStrategy`');

		$proxy->unknownMethod();
	}
}
