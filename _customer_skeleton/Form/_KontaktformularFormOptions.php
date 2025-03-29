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
	public function modifyForm(Form $form, ?Page $page = null): static {
		return parent::modifyForm($form, $page);
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, ?Page $page = null): static {
		//if (!$form->isSubmitted() && $formElement->identifier === 'email') {
		//	$formElement->value = 'foo@bar.com';
		//}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getProtectionOptions(string $identifier): ?array {
		// Different timeout for ip checks?
		//if ($identifier === 'ipCheck') {
		//	return [
		//		'checkTimeout' => 120,
		//	];
		//}

		return null;
	}
}
