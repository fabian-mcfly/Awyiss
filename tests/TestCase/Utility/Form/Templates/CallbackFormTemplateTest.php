<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form\Templates;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\Templates\CallbackFormTemplate;


/**
 * Test case for CallbackFormTemplate
 *
 * @see \Awyiss\Utility\Form\Templates\CallbackFormTemplate
 */
class CallbackFormTemplateTest extends TestCase {
	/**
	 * Test getTitle
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\CallbackFormTemplate::getTitle()
	 */
	public function testGetTitle(): void {
		$this->assertSame(
			'forms::form_template_callback_form',
			CallbackFormTemplate::getTitle()
		);
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\CallbackFormTemplate::getElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetElements(): void {
		$elements = CallbackFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
		]);

		$this->assertCount(4, $elements);
		$this->assertSame(['name', 'phone', 'privacy_accepted', null], array_column($elements, 'identifier'));
		$this->assertSame(['Name', 'Telefon', 'Datenschutz akzeptiert', 'Absenden'], array_column($elements, 'title'));
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\CallbackFormTemplate::getElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetElementsIncludesTranslationsnForMultipleLanguages(): void {
		$elements = CallbackFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
			'en' => $this->fetchTable('Languages')->get(3), // English
		]);

		$this->assertSame([
			['de' => ['title' => 'Name'], 'en' => ['title' => 'Name']],
			['de' => ['title' => 'Telefon'], 'en' => ['title' => 'Phone']],
			['de' => ['title' => 'Datenschutz akzeptiert'], 'en' => ['title' => 'Privacy accepted']],
			['de' => ['title' => 'Absenden'], 'en' => ['title' => 'Submit']],
		], array_column($elements, '_translations'));
	}
}
