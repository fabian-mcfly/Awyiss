<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Bake;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\Bake\BakeTestTrait;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;


/**
 * Class TemplateCommandTest
 */
class TemplateCommandTest extends TestCase {
	use ConsoleIntegrationTestTrait;
	use BakeTestTrait;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();
	}


	/**
	 * @return void
	 */
	public function testPolicyCommandHelp(): void {
		$this->exec('bake template --help');

		$this->assertExitSuccess();

		$this->assertOutputContains('--folder');
	}


	/**
	 * @return void
	 */
	public function testContentTemplateCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Frontend' . DS . 'content' . DS . 'standard.twig';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'templates' . DS . 'Frontend' . DS . 'content' . DS . 'standard.twig';

		$this->exec('bake template content_templates content_template standard --prefix Frontend --controller content', ['a']);

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}


	/**
	 * @return void
	 */
	public function testContentTemplateWithFolderCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'subfolder' . DS . 'Frontend' . DS . 'content' . DS . 'standard.twig';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'templates' . DS . 'Frontend' . DS . 'content' . DS . 'standard.twig';

		$this->exec('bake template content_templates content_template standard --prefix Frontend --folder ' . CUSTOM_DIR . DS . 'templates' . DS . 'subfolder --controller content');

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}


	/**
	 * @return void
	 */
	public function testEmailTemplateCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Frontend' . DS . 'email' . DS . 'standard.twig';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'templates' . DS . 'Frontend' . DS . 'email' . DS . 'standard.twig';

		$this->exec('bake template email_templates email_template standard --prefix Frontend --controller email', ['a']);

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}


	/**
	 * @return void
	 */
	public function testPageTemplateCommand(): void {
		$this->generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Frontend' . DS . 'page' . DS . 'standard.twig';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'templates' . DS . 'Frontend' . DS . 'page' . DS . 'standard.twig';

		$this->exec('bake template page_templates page_template standard --prefix Frontend --controller page', ['a']);

		$this->assertExitSuccess();

		$result = file_get_contents($this->generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}


	/**
	 * @return void
	 */
	public function testWidgetTemplateCommand(): void {
		$generatedFile = ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Frontend' . DS . 'widget' . DS . 'standard.twig';
		$comparisonEntityFile = ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'templates' . DS . 'Frontend' . DS . 'widget' . DS . 'standard.twig';

		$this->exec('bake template widget_templates widget_template standard --prefix Frontend --controller widget', ['a']);

		$this->assertExitSuccess();

		$result = file_get_contents($generatedFile);
		$this->assertSameAsFile($comparisonEntityFile, $result);
	}
}
