<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
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
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm('Frontend');
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
			],
		]);

		$this->response = $this->createMock(Response::class);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplay(): void {
		$page = $this->fetchTable('Pages')->find()->first();

		// Without any form protection
		Configure::write('Awyiss.Forms.Frontend.protection.methods', []);

		$output = (string)$this->cell('Frontend/Form', [
			'contact',
			$page,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);
		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		// The form uses the current timestamp for the id of the action. The comparion file uses the string '{{now}}' instead.
		$output = preg_replace('/data-[0-9]{10}/', 'data-{{now}}', $output);
		$output = preg_replace('/formElementsChecksum = \'[0-9]{10}/', 'formElementsChecksum = \'{{now}}', $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Form-Contact.txt', $output);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDisplayForUnknownForm(): void {
		$page = $this->fetchTable('Pages')->find()->first();

		$output = (string)$this->cell('Frontend/Form', [
			'unknown',
			$page,
		]);

		$this->assertSame('', $output);
	}
}
