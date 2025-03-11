<?php declare(strict_types=1);


namespace FoobarCustomer\Form;


use Awyiss\Form\FormOptions;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Validation\Validator;


/**
 * Example class to show how to set validation rules for a form,
 * and how to modify the form and form elements.
 *
 * Sets the validation rules for the contact form with the identifier 'kontaktformular'.
 */
class KontaktformularFormOptions extends FormOptions {
	/**
	 * @inheritDoc
	 */
	public function getValidator(Validator $validator, Form $form): Validator {
		/** @noinspection PhpVariableNamingConventionInspection */
		$validator = parent::getValidator($validator, $form);

		$validator->email('email');

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyForm(Form $form, array $requestData, bool $submitted, Page $page): void {
		// Do nothing
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, array $requestData, bool $submitted, Page $page): void {
		if (!$submitted && $formElement->identifier === 'email') {
			$formElement->value = 'foo@bar.com';
		}
	}
}
