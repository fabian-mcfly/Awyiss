<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form\Templates;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\Templates\ContactFormTemplate;


/**
 * Test case for ContactFormTemplate
 *
 * @see \Awyiss\Utility\Form\Templates\ContactFormTemplate
 */
class ContactFormTemplateTest extends TestCase {
	/**
	 * Test getTitle
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\ContactFormTemplate::getTitle()
	 */
	public function testGetTitle(): void {
		$this->assertSame(
			'forms::form_template_contact_form',
			ContactFormTemplate::getTitle()
		);
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\ContactFormTemplate::getElements()
	 */
	public function testGetElements(): void {
		$elements = ContactFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
		]);

		$this->assertCount(8, $elements);
		$this->assertSame(['title', 'firstname', 'lastname', 'phone', 'email', 'message', 'privacy_accepted', null], array_column($elements, 'identifier'));
		$this->assertSame(['Titel', 'Vorname', 'Nachname', 'Telefon', 'E-Mail', 'Nachricht', 'Datenschutz akzeptiert', 'Absenden'], array_column($elements, 'title'));
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\ContactFormTemplate::getElements()
	 */
	public function testGetElementsIncludesTranslationsnForMultipleLanguages(): void {
		$elements = ContactFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
			'en' => $this->fetchTable('Languages')->get(3), // English
		]);

		$this->assertSame([
			['de' => ['title' => 'Titel'], 'en' => ['title' => 'Title']],
			['de' => ['title' => 'Vorname'], 'en' => ['title' => 'Firstname']],
			['de' => ['title' => 'Nachname'], 'en' => ['title' => 'Lastname']],
			['de' => ['title' => 'Telefon', 'optional_placeholder' => '(optional)'], 'en' => ['title' => 'Phone', 'optional_placeholder' => '(optional)']],
			['de' => ['title' => 'E-Mail'], 'en' => ['title' => 'Email']],
			['de' => ['title' => 'Nachricht'], 'en' => ['title' => 'Message']],
			['de' => ['title' => 'Datenschutz akzeptiert'], 'en' => ['title' => 'Privacy accepted']],
			['de' => ['title' => 'Absenden'], 'en' => ['title' => 'Submit']],
		], array_column($elements, '_translations'));
	}
}
