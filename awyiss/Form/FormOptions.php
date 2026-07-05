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
	 * @var \Awyiss\Model\Entity\Form
	 */
	protected Form $form;
	/**
	 * @var \Awyiss\Model\Entity\Page|null
	 */
	protected ?Page $page = null;
	/**
	 * Indicates whether the real sender should be used as the sender (= empty value),
	 * or if the site owner's email should be used as the sender (= safe email address).
	 * This should ensure that no mail server denies the email
	 * due to the sender not having the same origin as the site.
	 *
	 * @var string|null $safeRealSender
	 */
	protected ?string $safeRealSender;


	/**
	 * @inheritDoc
	 */
	public function __construct(Form $form, ?Page $page = null) {
		/** @var class-string<\Cake\Mailer\Mailer> $className */
		$className = App::className('Mailer', 'Mailer');
		$mailer = new $className('default');

		// Make sure to only use the main domain (e.g. example.com instead of sub.example.com or www.example.com)
		$domain = $mailer->getDomain();
		if (substr_count($domain, '.') > 1) {
			$domainParts = explode('.', $domain);
			$domain = implode('.', array_slice($domainParts, -2));
		}

		$this->form = $form;
		$this->safeRealSender = 'noreply@' . $domain;
		$this->page = $page;
	}


	/**
	 * @inheritDoc
	 */
	public function setValidationRules(Validator $validator): Validator {
		if (!$this->form->formElements?->count()) {
			return $validator;
		}

		$formElements = $this->form->formElements->listNested()->toList();

		/** @var \Awyiss\Model\Entity\FormElement $formElement */
		foreach ($formElements as $formElement) {
			if ($formElement->required) {
				$this->setFieldRequired($validator, $formElement);
			}

			if (in_array($formElement->type, ['date', 'time', 'datetime'])) {
				$validator->add($formElement->identifier, [
					$formElement->type => ['rule' => $formElement->type],
				]);
			}
			elseif (
				($formElement->type === 'checkbox' && $formElement->options && count($formElement->options) > 1) ||
				$formElement->type === 'selectMultiple'
			) {
				$options = $formElement->options;
				$validator->add($formElement->identifier, [
					'inList' => [
						'rule' => function (mixed $value) use ($options): bool {
							$possibleValues = array_keys($options);

							// If not all values are in the possible values, the value is invalid
							return !array_diff($value, $possibleValues);
						},
					],
				]);
			}
			elseif (in_array($formElement->type, ['checkbox', 'radio', 'select'])) {
				$keys = array_keys($formElement->options ?? []);

				if (
					in_array($formElement->type, ['checkbox', 'radio']) &&
					!$formElement->required
				) {
					$keys[] = '';
				}

				$validator->add($formElement->identifier, [
					'inList' => ['rule' => ['inList', $keys]],
				]);
			}
			elseif ($formElement->type === 'email') {
				$validator->add($formElement->identifier, [
					'email' => ['rule' => 'email'],
				]);
			}
		}

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyForm(): static {
		// Do nothing
		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement): static {
		// Do nothing
		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function setConditionalRecipient(): static {
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = FactoryLocator::get('Table')->get('Forms');
		$formsTable->loadInto($this->form, ['FormConditionalRecipients']);

		if (!$this->form->conditionalRecipients) {
			return $this;
		}

		/** @var \Awyiss\Form\FormConditionalRecipients $conditionalRecipientsClass */
		$conditionalRecipientsClass = App::className('FormConditionalRecipients', 'Form');
		$conditionalRecipients = new $conditionalRecipientsClass($this->form, $this->page);

		$conditionalRecipients->setProcessStrategy($this->form->conditionalRecipientsStrategy);

		$recipient = $conditionalRecipients->getMatchingRecipient($this->form->conditionalRecipients, $this->form->getFormData());
		if ($recipient) {
			$this->form->ownerEmail = $recipient;
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
		elseif ($formElement->type === 'selectMultiple') {
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
