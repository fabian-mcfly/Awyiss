<?php declare(strict_types=1);


namespace Awyiss\Form;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Entity\Page;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * Default form options
 */
class FormOptions implements FormOptionsInterface {
	/**
	 * Indicates whether the real sender should be used as the sender (= empty value),
	 * or if the site owner's email should be used as the sender (= safe email address).
	 * This should ensure that no mailserver denies the email
	 * due to the sender not having the same origin as the site.
	 *
	 * @var string|null $safeRealSender
	 */
	protected ?string $safeRealSender;


	/**
	 * Constructor
	 */
	public function __construct() {
		/** @var class-string<\Cake\Mailer\Mailer> $ls_className */
		$ls_className = App::className('Mailer', 'Mailer');
		$lo_mailer = new $ls_className('default');

		$this->safeRealSender = 'noreply@' . $lo_mailer->getMessage()->getDomain();
	}


	/**
	 * @inheritDoc
	 */
	public function getValidator(Validator $validator, Form $form): Validator {
		if (!$form->formElements->count()) {
			return $validator;
		}

		$lo_formElements = $form->formElements->listNested()->toList();

		/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
		foreach ($lo_formElements as $lo_formElement) {
			if ($lo_formElement->required) {
				$this->setFieldRequired($validator, $lo_formElement);
			}

			if (in_array($lo_formElement->type, ['date', 'time', 'datetime'])) {
				$validator->add($lo_formElement->identifier, [
					$lo_formElement->type => ['rule' => $lo_formElement->type],
				]);
			}
			elseif (
				($lo_formElement->type === 'checkbox' && $lo_formElement->options && count($lo_formElement->options) > 1) ||
				$lo_formElement->type === 'select_multiple'
			) {
				$la_options = $lo_formElement->options;
				$validator->add($lo_formElement->identifier, [
					'inList' => [
						'rule' => function (mixed $value) use ($la_options): bool {
							$la_possibleValues = array_keys($la_options);

							// If not all values are in the possible values, the value is invalid
							return !array_diff($value, $la_possibleValues);
						},
					],
				]);
			}
			elseif (in_array($lo_formElement->type, ['checkbox', 'radio', 'select'])) {
				$la_keys = array_keys($lo_formElement->options ?? []);

				if (
					in_array($lo_formElement->type, ['checkbox', 'radio']) &&
					!$lo_formElement->required
				) {
					$la_keys[] = '';
				}

				$validator->add($lo_formElement->identifier, [
					'inList' => ['rule' => ['inList', $la_keys]],
				]);
			}
			elseif ($lo_formElement->type === 'email') {
				$validator->add($lo_formElement->identifier, [
					'email' => ['rule' => 'email'],
				]);
			}
		}

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyForm(Form $form, ?Page $page = null): static {
		// Do nothing
		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, ?Page $page = null): static {
		// Do nothing
		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function setConditionalRecipient(Form $form, ?Page $page = null): static {
		/** @var \Awyiss\Model\Table\FormsTable $lo_formsTable */
		$lo_formsTable = FactoryLocator::get('Table')->get('Forms');
		$lo_formsTable->loadInto($form, ['FormConditionalRecipients']);

		if (!$form->formConditionalRecipients) {
			return $this;
		}

		/** @var \Awyiss\Form\FormConditionalRecipients $ls_conditionalRecipientsClass */
		$ls_conditionalRecipientsClass = App::className('FormConditionalRecipients', 'Form');
		$lo_conditionalRecipients = new $ls_conditionalRecipientsClass($form, $page);

		$lo_conditionalRecipients->setProcessStrategy($form->conditionalRecipientsStrategy);

		$ls_recipient = $lo_conditionalRecipients->getMatchingRecipient($form->formConditionalRecipients, $form->getFormData());
		if ($ls_recipient) {
			$form->ownerEmail = $ls_recipient;
		}

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getProtectionOptions(string $identifier): ?array {
		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function getSafeRealSender(): ?string {
		return $this->safeRealSender;
	}


	/**
	 * @inheritDoc
	 */
	public function setSafeRealSender(?string $safeRealSender): static {
		$this->safeRealSender = $safeRealSender;

		return $this;
	}


	/**
	 * @param \Awyiss\Validation\Validator $validator
	 * @param \Awyiss\Model\Entity\FormElement $formElement
	 * @return void
	 */
	protected function setFieldRequired(Validator $validator, FormElement $formElement): void {
		$validator->requirePresence($formElement->identifier);

		if (in_array($formElement->type, ['text', 'textarea', 'email', 'radio', 'select'])) {
			$validator->notEmptyString($formElement->identifier);
			$validator->add($formElement->identifier, [
				'notBlank' => ['rule' => 'notBlank'],
			]);
		}
		elseif ($formElement->type === 'select_multiple') {
			$validator->notEmptyArray($formElement->identifier);
		}
		elseif ($formElement->type === 'checkbox') {
			if ($formElement->options && count($formElement->options) > 1) {
				$validator->notEmptyArray($formElement->identifier);
			}
			else {
				$validator->notEmptyString($formElement->identifier);
			}
		}
		elseif ($formElement->type === 'date') {
			$validator->notEmptyDate($formElement->identifier);
		}
		elseif ($formElement->type === 'time') {
			$validator->notEmptyTime($formElement->identifier);
		}
		elseif ($formElement->type === 'datetime') {
			$validator->notEmptyDateTime($formElement->identifier);
		}
		elseif ($formElement->type === 'file') {
			$validator->notEmptyFile($formElement->identifier);
		}
	}
}
