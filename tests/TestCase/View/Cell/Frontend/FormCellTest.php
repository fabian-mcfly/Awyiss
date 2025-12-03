<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\CellTrait;


/**
 * FormCellTest class
 */
class FormCellTest extends TestCase {
	use CellTrait;
	use IntegrationTestTrait;


	/**
	 * @var \Cake\Http\Response
	 */
	protected Response $response;
	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm('Frontend');
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		Awyiss::loadConfiguration('xy', 'yx');

		$this->loadRoutes();

		$this->request = new ServerRequest([
			'url' => '/xy/dummy-slug',
			'params' => [
				'lang' => 'xy',
				'slug' => 'dummy-slug',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$this->response = $this->createMock(Response::class);
		$this->view = new FrontendView($this->request);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::display()
	 */
	public function testDisplay(): void {
		$page = $this->fetchTable('Pages')->find()->first();

		// Without any form protection
		Configure::write('Awyiss.Forms.Frontend.protection.methods', []);

		$output = (string)$this->cell('Frontend/Form', [
			'contact',
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'includeWrapper' => true,
				'singleColumnBreakpoint' => 768.00,
			],
		]);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		// The form uses the current timestamp for the id of the action. The comparison file uses the string '{{now}}' instead.
		$output = preg_replace('/data-[0-9]{10}/', 'data-{{now}}', $output);
		$output = preg_replace('/formElementsChecksum = \'[0-9]{10}/', 'formElementsChecksum = \'{{now}}', $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Form-Contact.txt', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::display()
	 */
	public function testDisplayForUnknownForm(): void {
		$page = $this->fetchTable('Pages')->find()->first();

		$output = (string)$this->cell('Frontend/Form', [
			'unknown',
			$page,
			$this->view,
		]);

		$this->assertSame('', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::display()
	 */
	public function testDisplayLoadsFormEntry(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$this->request = $this->request->withParam('formEntry', 'aa43b23308dd6bdff9edb15deb2b3b41');

		$output = (string)$this->cell('Frontend/Form', [
			1,
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringNotContainsString('<form', $output);
		$this->assertStringNotContainsString('$firstname', $output);
		$this->assertStringNotContainsString('$lastname', $output);
		$this->assertStringContainsString('<div class="Form Form-SuccessMessage"', $output);
	}



	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::display()
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlRequiredAltAttribute
	 */
	public function testDisplayLoadsFormEntryAndReplacesAwyissImageTagInSuccessMessage(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$this->request = $this->request->withParam('formEntry', 'aa43b23308dd6bdff9edb15deb2b3b41');

		$output = (string)$this->cell('Frontend/Form', [
			1,
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringContainsString('<div class="Form Form-SuccessMessage" id="Form-Contact">', $output);
		$this->assertStringContainsString('<picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif"', $output);
	}



	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::display()
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlRequiredAltAttribute
	 */
	public function testDisplayLoadsFormEntryAndReplacesAwyissImageTagInSuccessMessageWithColumnWidth(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$this->request = $this->request->withParam('formEntry', 'aa43b23308dd6bdff9edb15deb2b3b41');

		$output = (string)$this->cell('Frontend/Form', [
			1,
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 50,
			],
		]);

		$this->assertStringContainsString('<div class="Form Form-SuccessMessage" id="Form-Contact">', $output);
		$this->assertStringContainsString('<picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w768].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w768].avif"', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::parseAwyissImageTags()
	 * @noinspection HtmlRequiredAltAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTagInFreeTextFormElement(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$output = (string)$this->cell('Frontend/Form', [
			2,
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringContainsString('<p>Form element with inline img tag</p><p><picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1152].avif"', $output);
		$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<source media="(width <= 1280px)" data-srcset="_resized/dummypath/logo-awyiss-[w1024].avif 1x, _resized/dummypath/logo-awyiss-[w2048].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1152].avif"', $output);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p>', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\FormCell::parseAwyissImageTags()
	 * @noinspection HtmlRequiredAltAttribute
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTagInFreeTextFormElementWithColumnWidth(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$output = (string)$this->cell('Frontend/Form', [
			2,
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 50,
			],
		]);

		$this->assertStringContainsString('<p>Form element with inline img tag</p><p><picture>', $output);
		$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<source media="(width <= 1280px)" data-srcset="../awyiss/Command/Media/TestFiles/_resized/logo-awyiss-[w512].avif 1x, _resized/dummypath/logo-awyiss-[w1024].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p>', $output);
	}
}
