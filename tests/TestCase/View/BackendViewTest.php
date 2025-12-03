<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Language;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Twig\FileLoader;
use Awyiss\View\BackendView;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Twig\Environment;


/**
 * BackendViewTest class
 */
class BackendViewTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm('Backend');
		$this->view = new BackendView();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::initialize()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testInitialize(): void {
		$view = $this->getMockBuilder(BackendView::class)
			->onlyMethods(['addHelpers', 'addTwigGlobals'])
			->getMock();

		$view->expects($this->once())->method('addHelpers');
		$view->expects($this->once())->method('addTwigGlobals');

		$view->initialize();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::initialize()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddHelpers(): void {
		$this->view->initialize();

		$helpers = $this->view->helpers()->loaded();

		$this->assertContains('Asset', $helpers);
		$this->assertContains('Attributes', $helpers);
		$this->assertContains('Identity', $helpers);
		$this->assertContains('Authorization', $helpers);
		$this->assertContains('Categories', $helpers);
		$this->assertContains('Flash', $helpers);
		$this->assertContains('Form', $helpers);
		$this->assertContains('Html', $helpers);
		$this->assertContains('Locale', $helpers);
		$this->assertContains('Media', $helpers);
		$this->assertContains('Paginator', $helpers);
		$this->assertContains('Survey', $helpers);
		$this->assertContains('SystemOrder', $helpers);
		$this->assertContains('Url', $helpers);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::initialize()
	 * @throws \Twig\Error\LoaderError
	 */
	public function testInitializeSetsTwigGlobals(): void {
		$view = $this->getMockBuilder(BackendView::class)
			->onlyMethods(['addTwigGlobals'])
			->getMock();

		$view->expects($this->once())->method('addTwigGlobals');

		$view->initialize();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::addTwigGlobals()
	 * @throws \ReflectionException
	 */
	public function testAddTwigGlobals(): void {
		$twig = $this->getMockBuilder(Environment::class)
			->setConstructorArgs([
				new FileLoader(['.twig']),
			])
			->onlyMethods(['addGlobal'])
			->getMock();

		$view = $this->getMockBuilder(BackendView::class)
			->onlyMethods(['getTwig'])
			->getMock();

		$view->expects($this->once())->method('getTwig')->willReturn($twig);

		$twig->expects($this->exactly(13))->method('addGlobal');

		$this->callProtectedMethod($view, 'addTwigGlobals');
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::getLoginLogoPath()
	 * @throws \ReflectionException
	 */
	public function testGetLoginLogoPath(): void {
		$path = $this->callProtectedMethod($this->view, 'getLoginLogoPath');

		$this->assertSame('/assets/img/login-logo.png', $path);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::cleanLanguage()
	 * @throws \ReflectionException
	 */
	public function testCleanLanguage(): void {
		$language = new Language([
			'shortcode' => 'de',
			'timezone' => 'Europe/Berlin',
			'locale' => 'de_DE',
			'title' => 'Deutsch',
			'active' => true,
			'deleted' => false,
			'createdBy' => 1,
			'createdOn' => new DateTime(),
		]);

		$cleanedLanguage = $this->callProtectedMethod($this->view, 'cleanLanguage', $language);

		$this->assertInstanceOf(Language::class, $cleanedLanguage);

		$languageArray = $cleanedLanguage->toArray();

		$this->assertNotSame($language, $cleanedLanguage);

		$this->assertEquals('Europe/Berlin', $language->timezone);
		$this->assertEquals('de_DE', $language->locale);
		$this->assertEquals(1, $language->createdBy);
		$this->assertInstanceOf(DateTime::class, $language->createdOn);

		$this->assertEquals([
			'shortcode' => 'de',
			'timezone' => 'Europe/Berlin',
			'locale' => 'de_DE',
			'title' => 'Deutsch',
			'label' => 'Deutsch',
		], $languageArray);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::addFrontendLanguage()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddFrontendLanguage(): void {
		$this->view->initialize();

		$twig = $this->createMock(Environment::class);

		$twig->expects($this->atLeastOnce())->method('addGlobal');

		$this->callProtectedMethod($this->view, 'addFrontendLanguage', $twig);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\BackendView::addUserLanguage()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @throws \Twig\Error\LoaderError
	 */
	public function testAddUserLanguage(): void {
		$this->view->initialize();

		$twig = $this->createMock(Environment::class);

		$twig->expects($this->atLeastOnce())->method('addGlobal');

		$this->callProtectedMethod($this->view, 'addUserLanguage', $twig);
	}
}
