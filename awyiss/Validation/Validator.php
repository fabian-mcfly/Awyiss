<?php


namespace Awyiss\Validation;


use Cake\Validation\ValidationSet;


class Validator extends \Cake\Validation\Validator {
	/**
	 * @param string $as_field
	 * @param \Cake\Validation\ValidationSet $ao_rules
	 * @param array $aa_data
	 * @param bool $ab_newRecord
	 *
	 * @return array
	 */
	protected function _processRules (string $as_field, ValidationSet $ao_rules, array $aa_data, bool $ab_newRecord): array {
		$la_errors = [];
		// Loading default provider in case there is none
		$this->getProvider('default');
		$ls_message = 'The provided value is invalid';

		if ($this->_useI18n) {
			$ls_message = __('form_validation::invalid_value');
		}

		foreach ($ao_rules as $ls_name => $lo_rule) {
			$lx_result = $lo_rule->process($aa_data[ $as_field ], $this->_providers, [
				'newRecord' => $ab_newRecord,
				'data' => $aa_data,
				'field' => $as_field,
			]);

			if ($this->_useI18n) {
				$ls_message = __('form_validation::' . $ls_name, $lo_rule->get('pass'));
			}

			if ($lx_result === TRUE) {
				continue;
			}

			$la_errors[ $ls_name ] = $ls_message;
			if (is_array($lx_result) && $ls_name === static::NESTED) {
				$la_errors = $lx_result;
			}
			if (is_string($lx_result)) {
				$la_errors[ $ls_name ] = $lx_result;
			}

			if ($lo_rule->isLast()) {
				break;
			}
		}

		return $la_errors;
	}


	/**
	 * @param string $field
	 *
	 * @return null|string
	 */
	public function getRequiredMessage (string $field): ?string {
		if ( ! isset($this->_fields[ $field ])) {
			return NULL;
		}

		$defaultMessage = 'This field is required';
		if ($this->_useI18n) {
			$defaultMessage = __d('cake', 'This field is required');
		}

		return $this->_presenceMessages[ $field ] ?? $defaultMessage;
	}


	/**
	 * @param string $field
	 *
	 * @return null|string
	 */
	public function getNotEmptyMessage (string $field): ?string {
		if ( ! isset($this->_fields[ $field ])) {
			return NULL;
		}

		$defaultMessage = 'This field cannot be left empty';
		if ($this->_useI18n) {
			$defaultMessage = __d('cake', 'This field cannot be left empty');
		}

		foreach ($this->_fields[ $field ] as $rule) {
			if ($rule->get('rule') === 'notBlank' && $rule->get('message')) {
				return $rule->get('message');
			}
		}

		return $this->_allowEmptyMessages[ $field ] ?? $defaultMessage;
	}
}