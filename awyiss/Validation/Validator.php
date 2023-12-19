<?php

/**
 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
 */

declare(strict_types=1);


namespace Awyiss\Validation;


use Cake\Utility\Inflector;
use Cake\Validation\ValidationSet;


class Validator extends \Cake\Validation\Validator {
	protected string $i18nDomain = '';


	public function setI18nDomain (string $as_domain) {
		$this->i18nDomain = Inflector::underscore($as_domain);
	}


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
		//$ls_message = __('form_validation::invalid_value');

		foreach ($ao_rules as $ls_name => $lo_rule) {
			$lx_result = $lo_rule->process($aa_data[ $as_field ], $this->_providers, [
				'newRecord' => $ab_newRecord,
				'data' => $aa_data,
				'field' => $as_field,
			]);

			if ($lx_result === TRUE) {
				continue;
			}

			if (is_string($lx_result) || (is_array($lx_result) && $ls_name === static::NESTED)) {
				if (is_string($lx_result)) {
					$la_errors[ $ls_name ] = $lx_result;
				}
				else {
					$la_errors = $lx_result;
				}

				if ($lo_rule->isLast()) {
					break;
				}

				continue;
			}

			$la_pass = [
				'field' => __($this->i18nDomain . '::' . Inflector::underscore($as_field)),
			];

			if ($lx_pass = ($lo_rule->get('pass')[0] ?? [])) {
				if ($ls_name == 'sameAs' || $ls_name == 'notSameAs') {
					$lx_pass = __($this->i18nDomain . '::' . Inflector::underscore($lx_pass));
				}
				elseif ($ls_name == 'dateTime') {
					$lx_pass = $lx_pass[0] ?? 'Ymd';
				}
				elseif ($ls_name == 'inList') {
					$lx_pass = implode(',', $lx_pass);
				}
				elseif (!is_scalar($lx_pass)) {
					throw new \RuntimeException(sprintf('Missing translation informations for `%s`, passed arguments: `%s`', $ls_name, print_r($lx_pass, TRUE)));
				}
				$la_pass[ $ls_name ] = $lx_pass;
			}

			/*try {
				$ls_message = __('form_validation::error_' . Inflector::underscore($ls_name), $la_pass);
			}
			catch (\Exception $e) {
				dump($ls_name, $lx_pass);
				dd($this->ls_i18nDomain, $ls_name, $la_pass, $e->getMessage());
			}*/

			$la_errors[ $ls_name ] = __('form_validation::error_' . Inflector::underscore($ls_name), $la_pass);

			if ($lo_rule->isLast()) {
				break;
			}
		}

		return $la_errors;
	}


	/**
	 * @param string $as_field
	 *
	 * @return null|string
	 */
	public function getRequiredMessage (string $as_field): ?string {
		if ( ! isset($this->_fields[ $as_field ])) {
			return NULL;
		}

		$ls_defaultMessage = __('form_validation::error_required');

		return $this->_presenceMessages[ $as_field ] ?? $ls_defaultMessage;
	}


	/**
	 * @param string $as_field
	 *
	 * @return null|string
	 */
	public function getNotEmptyMessage (string $as_field): ?string {
		if ( ! isset($this->_fields[ $as_field ])) {
			return NULL;
		}

		$ls_defaultMessage = __('form_validation::error_not_empty');

		foreach ($this->_fields[ $as_field ] as $rule) {
			if ($rule->get('rule') === 'notBlank' && $rule->get('message')) {
				return $rule->get('message');
			}
		}

		return $this->_allowEmptyMessages[ $as_field ] ?? $ls_defaultMessage;
	}
}