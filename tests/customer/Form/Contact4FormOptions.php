<?php declare(strict_types=1);


namespace Customer\Form;


use Awyiss\Form\FormOptions;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Validation\Validator;


/**
 * Contact3FormOptions
 * Sets the validation rules for the contact form with the identifier 'contact3'.
 */
class Contact4FormOptions extends FormOptions {
	/**
	 * @inheritDoc
	 */
	public function setValidationRules(Validator $validator): Validator {
		/** @noinspection PhpVariableNamingConventionInspection */
		$validator = parent::setValidationRules($validator);

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyForm(): static {
		$this->form->identifier = 'new_contact4';

		return parent::modifyForm();
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement): static {
		if (!$this->form->isSubmitted() && $formElement->identifier === 'email') {
			$formElement->value = 'foo@bar.com';
		}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function setConditionalRecipient(): static {
		if ($this->form->getFormData('email') === 'importantclient@example.com') {
			$this->form->ownerEmail = 'importantclient@cms.de';
		}

		return $this;
	}
}
