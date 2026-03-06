<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form\Templates;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\Templates\JobApplicationFormTemplate;


/**
 * Test case for JobApplicationFormTemplate
 *
 * @see \Awyiss\Utility\Form\Templates\JobApplicationFormTemplate
 */
class JobApplicationFormTemplateTest extends TestCase {
	/**
	 * Test getTitle
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\JobApplicationFormTemplate::getTitle()
	 */
	public function testGetTitle(): void {
		$this->assertSame(
			'forms::form_template_job_application_form',
			JobApplicationFormTemplate::getTitle()
		);
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\JobApplicationFormTemplate::getElements()
	 */
	public function testGetElements(): void {
		$elements = JobApplicationFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
		]);

		$this->assertCount(8, $elements);
		$this->assertSame(['title', 'firstname', 'lastname', 'phone', 'email', null, 'privacyAccepted', null], array_column($elements, 'identifier'));
		$this->assertSame(['Titel', 'Vorname', 'Nachname', 'Telefon', 'E-Mail', 'Datenschutz akzeptiert', 'Absenden'], array_column($elements, 'title'));
	}


	/**
	 * Test getElements
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\Templates\JobApplicationFormTemplate::getElements()
	 */
	public function testGetElementsIncludesTranslationsnForMultipleLanguages(): void {
		$elements = JobApplicationFormTemplate::getElements([
			'de' => $this->fetchTable('Languages')->get(1), // German
			'en' => $this->fetchTable('Languages')->get(3), // English
		]);

		$this->assertSame([
			['de' => ['title' => 'Titel'], 'en' => ['title' => 'Title']],
			['de' => ['title' => 'Vorname'], 'en' => ['title' => 'Firstname']],
			['de' => ['title' => 'Nachname'], 'en' => ['title' => 'Lastname']],
			['de' => ['title' => 'Telefon', 'optionalPlaceholder' => '(optional)'], 'en' => ['title' => 'Phone', 'optionalPlaceholder' => '(optional)']],
			['de' => ['title' => 'E-Mail'], 'en' => ['title' => 'Email']],
			[
				'de' => ['text' => '<strong>Bewerbungsunterlagen</strong><br>Platz für Lebenslauf, Zeugnisse und ein optionales Anschreiben'],
				'en' => ['text' => '<strong>Application Documents</strong><br>Space for CV, certificates, and an optional cover letter'],
			],
			['de' => ['title' => 'Datenschutz akzeptiert'], 'en' => ['title' => 'Privacy accepted']],
			['de' => ['title' => 'Absenden'], 'en' => ['title' => 'Submit']],
		], array_column($elements, '_translations'));
	}
}
