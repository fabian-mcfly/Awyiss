<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form\Templates;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\Templates\AppointmentFormTemplate;


/**
 * Test case for AppointmentFormTemplate
 *
 * @see \Awyiss\Utility\Form\Templates\AppointmentFormTemplate
 */
class AppointmentFormTemplateTest extends TestCase {
	/**
	 * Test getTitle
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\AppointmentFormTemplate::getTitle()
	 */
	public function testGetTitle(): void {
		$this->assertSame(
			'forms::form_template_appointment_form',
			AppointmentFormTemplate::getTitle()
		);
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\AppointmentFormTemplate::getElements()
	 */
	public function testGetElements(): void {
		$elements = AppointmentFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
		]);

		$this->assertCount(8, $elements);
		$this->assertSame(['title', 'name', 'phone', 'email', 'datetime', 'message', 'privacy_accepted', null], array_column($elements, 'identifier'));
		$this->assertSame(['Titel', 'Name', 'Telefon', 'E-Mail', 'Datum/Uhrzeit', 'Nachricht', 'Datenschutz akzeptiert', 'Absenden'], array_column($elements, 'title'));
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\AppointmentFormTemplate::getElements()
	 */
	public function testGetElementsIncludesTranslationsnForMultipleLanguages(): void {
		$elements = AppointmentFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
			'en' => $this->fetchTable('Languages')->get(3), // English
		]);

		$this->assertSame([
			['de' => ['title' => 'Titel'], 'en' => [ 'title' => 'Title']],
			['de' => ['title' => 'Name'], 'en' => [ 'title' => 'Name']],
			['de' => ['title' => 'Telefon', 'optional_placeholder' => '(optional)'], 'en' => ['title' => 'Phone', 'optional_placeholder' => '(optional)']],
			['de' => ['title' => 'E-Mail'], 'en' => [ 'title' => 'Email']],
			['de' => ['title' => 'Datum/Uhrzeit'], 'en' => [ 'title' => 'Date and Time']],
			['de' => ['title' => 'Nachricht'], 'en' => [ 'title' => 'Message']],
			['de' => ['title' => 'Datenschutz akzeptiert'], 'en' => [ 'title' => 'Privacy accepted']],
			['de' => ['title' => 'Absenden'], 'en' => [ 'title' => 'Submit']],
		], array_column($elements, '_translations'));
	}
}
