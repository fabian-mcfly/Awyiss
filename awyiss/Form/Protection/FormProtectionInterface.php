<?php declare(strict_types=1);


namespace Awyiss\Form\Protection;


use Awyiss\Form\FormOptionsInterface;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormEntry;
use Cake\View\View;


/**
 * Interface for form protection methods
 * Each protection is supposed to add some logic to the form
 * itself and handle the validation of the form data before
 * deciding if the form should be saved and/or emails sent.
 */
interface FormProtectionInterface {
	/**
	 * To place the protection after all form elements
	 */
	public const string POSITION_AFTER = 'after';
	/**
	 * To place the protection before the form elements
	 */
	public const string POSITION_BEFORE = 'before';
	/**
	 * To place the protection before the submit button
	 */
	public const string POSITION_BEFORE_SUBMIT = 'beforeSubmit';


	/**
	 * This method is called once when the form is initialized,
	 * the elements are fetched and the options loaded
	 *
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param array<\Awyiss\Model\Entity\FormElement> $formElements
	 * @param \Awyiss\Form\FormOptionsInterface $formOptions
	 * @param \Cake\View\View $view
	 * @return static
	 */
	public function initialize(Form $form, array $formElements, FormOptionsInterface $formOptions, View $view): static;


	/**
	 * This method returns the HTML code for the protection
	 * It is called multiple times, once for each position
	 *
	 * @param string $templatePosition
	 * @return string|null
	 */
	public function getHtml(string $templatePosition): ?string;


	/**
	 * This method return a strings if the data is invalid,
	 * or true if the data is valid.
	 *
	 * @param array $data
	 * @return string|true
	 */
	public function validateData(array $data): string|true;


	/**
	 * This method is called once the form has been
	 * submitted and validated.
	 * It's possible to remove either the errors,
	 * or set new ones.
	 *
	 * It's also possible to disable sending out emails.
	 *
	 * @param \Awyiss\Model\Entity\Form $form
	 * @return void
	 */
	public function modifyForm(Form $form): void;


	/**
	 * This method is called once the form has been submitted
	 * and emails have been sent.
	 *
	 * It's possible to modify the form entry before it's saved.
	 * For example, to add additional data in case the submission
	 * is considered spam.
	 *
	 * If the form entry should not be saved, but the user should
	 * see a success message, return true.
	 *
	 * If the form entry should not be saved, and the user should
	 * see an error message, return false.
	 *
	 * If the form entry should be saved, return the form entry.
	 *
	 * @param \Awyiss\Model\Entity\FormEntry $formEntry
	 * @return \Awyiss\Model\Entity\FormEntry|bool
	 */
	public function modifyFormEntry(FormEntry $formEntry): FormEntry|bool;
}
