<?php declare(strict_types=1);


namespace Customer\Form;


use Awyiss\Form\FormOptions;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Validation\Validator;


/**
 * Contact3FormOptions
 * Sets the validation rules for the contact form with the identifier 'contact3'.
 */
class Contact4FormOptions extends FormOptions {
	/**
	 * @inheritDoc
	 */
	public function setValidationRules(Validator $validator, Form $form): Validator {
		/** @noinspection PhpVariableNamingConventionInspection */
		$validator = parent::setValidationRules($validator, $form);

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyForm(Form $form, ?Page $page = null): static {
		$form->identifier = 'new_contact4';

		return parent::modifyForm($form, $page);
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, ?Page $page = null): static {
		if (!$form->isSubmitted() && $formElement->identifier === 'email') {
			$formElement->value = 'foo@bar.com';
		}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function setConditionalRecipient(Form $form, ?Page $page = null): static {
		if ($form->getFormData('email') === 'importantclient@example.com') {
			$form->ownerEmail = 'importantclient@cms.de';
		}

		return $this;
	}
}
