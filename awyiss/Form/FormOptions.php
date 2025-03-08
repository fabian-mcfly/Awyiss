<?php declare(strict_types=1);


namespace Awyiss\Form;


use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Validation\Validator;


class FormOptions implements FormOptionsInterface {
	/**
	 * @inheritDoc
	 */
	public function getValidator(Validator $validator, Form $form): Validator {
		if (!$form->formElements) {
			return $validator;
		}

		$lo_formElements = $form->formElements->listNested()->toList();

		/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
		foreach ($lo_formElements as $lo_formElement) {
			if ($lo_formElement->required) {
				$validator->requirePresence($lo_formElement->identifier);

				if (in_array($lo_formElement->type, ['text', 'textarea', 'email', 'radio', 'select'])) {
					$validator->notEmptyString($lo_formElement->identifier);
					$validator->add($lo_formElement->identifier, [
						'notBlank' => ['rule' => 'notBlank'],
					]);
				}
				elseif ($lo_formElement->type === 'select_multiple') {
					$validator->notEmptyArray($lo_formElement->identifier);
				}
				elseif ($lo_formElement->type === 'checkbox') {
					if ($lo_formElement->options && count($lo_formElement->options) > 1) {
						$validator->notEmptyArray($lo_formElement->identifier);
					}
					else {
						$validator->notEmptyString($lo_formElement->identifier);
					}
				}
				elseif ($lo_formElement->type === 'date') {
					$validator->notEmptyDate($lo_formElement->identifier);
				}
				elseif ($lo_formElement->type === 'time') {
					$validator->notEmptyTime($lo_formElement->identifier);
				}
				elseif ($lo_formElement->type === 'datetime') {
					$validator->notEmptyDateTime($lo_formElement->identifier);
				}
				elseif ($lo_formElement->type === 'file') {
					$validator->notEmptyFile($lo_formElement->identifier);
				}
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
				$validator->add($lo_formElement->identifier, [
					'inList' => ['rule' => ['inList', array_keys($lo_formElement->options ?? [])]],
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
	public function modifyForm(Form $form, array $requestData, bool $submitted): void {
		// Do nothing
	}


	/**
	 * @inheritDoc
	 */
	public function modifyFormElement(FormElement $formElement, Form $form, array $requestData, bool $submitted): void {
		// Do nothing
	}
}
