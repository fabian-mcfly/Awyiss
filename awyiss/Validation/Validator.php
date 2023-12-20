<?php declare(strict_types=1);


namespace Awyiss\Validation;


use Cake\Utility\Inflector;
use Cake\Validation\ValidationSet;
use RuntimeException;


/**
 * {@inheritDoc}
 *
 * Extended version that makes use of the `i18nDomain`-property to make validation errors translatable
 * per scope/domain/model.
 */
class Validator extends \Cake\Validation\Validator {
	protected string $i18nDomain = '';


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function offsetExists($as_field): bool {
		dd(__FILE__, __LINE__, debug_backtrace(2));


		return isset($this->_fields[ $as_field ]);
	}


	public function offsetSet($field, $rules): void {
		dd(__FILE__, __LINE__, debug_backtrace(2));
	}


	public function offsetUnset($field): void {
		dd(__FILE__, __LINE__, debug_backtrace(2));
	}


	public function remove(string $field, ?string $rule = NULL) {
		dd(__FILE__, __LINE__, debug_backtrace(2));
	}


	/**
	 * @param string $as_domain
	 *
	 * @return void
	 */
	public function setI18nDomain(string $as_domain): void {
		$this->i18nDomain = Inflector::underscore($as_domain);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validate(array $aa_data, bool $ab_newRecord = TRUE): array {
		$la_data = $this->underscoreFields($aa_data, TRUE);


		return parent::validate($la_data, $ab_newRecord);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function allowEmptyFor(string $as_field, ?int $ai_flags = NULL, $ax_when = TRUE, ?string $as_message = NULL) {
		$ls_field = $this->underscoreField($as_field);


		return parent::allowEmptyFor($ls_field, $ai_flags, $ax_when, $as_message);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpHierarchyChecksInspection <-- WHY?
	 */
	public function field(string $as_name, ?ValidationSet $ao_validationSet = NULL): ValidationSet {
		$ls_name = $this->underscoreField($as_name);

		if (empty($this->_fields[ $ls_name ])) {
			$lo_validationSet = $ao_validationSet ?: new ValidationSet();
			$this->_fields[ $ls_name ] = $lo_validationSet;

			//Allow an empty string per default. Makes much more sense
			$this->allowEmptyString($as_name);
		}


		return $this->_fields[ $ls_name ];
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function hasField(string $as_name): bool {
		$ls_name = $this->underscoreField($as_name);


		return isset($this->_fields[ $ls_name ]);
	}


	/**
	 * @param string $as_field
	 *
	 * @return NULL|string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getRequiredMessage(string $as_field): ?string {
		$ls_field = $this->underscoreField($as_field);

		if (!isset($this->_fields[ $ls_field ])) {
			return NULL;
		}

		$ls_defaultMessage = __dfx($this->i18nDomain, 'validation', $as_field, 'error_required');


		return $this->_presenceMessages[ $ls_field ] ?? $ls_defaultMessage;
	}


	/**
	 * @param string $as_field
	 *
	 * @return NULL|string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getNotEmptyMessage(string $as_field): ?string {
		$ls_field = $this->underscoreField($as_field);

		if (!isset($this->_fields[ $ls_field ])) {
			return NULL;
		}

		$ls_defaultMessage = __dfx($this->i18nDomain, 'validation', $as_field, 'error_not_empty');

		foreach ($this->_fields[ $ls_field ] as $lo_rule) {
			if ($lo_rule->get('rule') === 'notBlank' && $lo_rule->get('message')) {
				return $lo_rule->get('message');
			}
		}


		return $this->_allowEmptyMessages[ $ls_field ] ?? $ls_defaultMessage;
	}


	/**
	 * @param string $as_field
	 * @param ValidationSet $ao_rules
	 * @param array $aa_data
	 * @param bool $ab_newRecord
	 *
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _processRules(string $as_field, ValidationSet $ao_rules, array $aa_data, bool $ab_newRecord): array {
		$la_errors = [];
		// Loading default provider in case there is none
		$this->getProvider('default');
		//$ls_message = __d('validation', 'invalid_value');

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
				'field' => __df($this->i18nDomain, 'system', Inflector::underscore($as_field)),
			];

			if ($lx_pass = ($lo_rule->get('pass')[0] ?? [])) {
				if (in_array($ls_name, ['sameAs', 'notSameAs', 'compareWith', 'compareFields'])) {
					//No domain fallback here, since it certainly is a field inside the domain, not something generic
					$lx_pass = __dx($this->i18nDomain, $ls_name, Inflector::underscore($lx_pass));
				}
				elseif ($ls_name == 'dateTime') {
					$lx_pass = $lx_pass[0] ?? 'Ymd';
				}
				elseif ($ls_name == 'inList') {
					$lx_pass = implode(',', $lx_pass);
				}
				elseif (!is_scalar($lx_pass)) {
					throw new RuntimeException(sprintf('Missing translation informations for `%s`, passed arguments: `%s`', $ls_name, print_r($lx_pass, TRUE)));
				}
				else {
					dd($ls_name, $lx_pass, $lo_rule->get('pass'), __FILE__, __LINE__);
				}
				$la_pass[ $ls_name ] = $lx_pass;
			}

			/*try {
				$ls_message = __d('validation', 'error_' . Inflector::underscore($ls_name), $la_pass);
			}
			catch (\Exception $e) {
				dump($ls_name, $lx_pass);
				dd($this->ls_i18nDomain, $ls_name, $la_pass, $e->getMessage());
			}*/

			$la_errors[ $ls_name ] = __dfx($this->i18nDomain, 'validation', $ls_name, 'error_' . Inflector::underscore($ls_name), $la_pass);

			if ($lo_rule->isLast()) {
				break;
			}
		}


		return $la_errors;
	}


	/**
	 * Transforms the given array so that keys or values (depending on $ab_variableKey)
	 * are written in camelBacked-format.
	 *
	 * @param array $aa_fields
	 * @param bool $ab_variableKey
	 *
	 * @return array
	 */
	protected function underscoreFields(array $aa_fields, bool $ab_underscoreKeys = FALSE): array {
		$la_fields = [];

		foreach ($aa_fields as $lx_field => $lx_value) {
			$lx_mapped = ($ab_underscoreKeys ? 'lx_field' : 'lx_value');
			$$lx_mapped = $this->underscoreField($$lx_mapped);

			$la_fields[ $lx_field ] = $lx_value;
		}


		return $la_fields;
	}


	/**
	 * @param mixed $ax_field
	 *
	 * @return mixed
	 */
	protected function underscoreField(mixed $ax_field): mixed {
		if (!$ax_field || !is_string($ax_field) || str_starts_with($ax_field, '_')) {
			return $ax_field;
		}

		if (($li_lastPos = strrpos($ax_field, '.')) !== FALSE) {
			$ls_prefix = substr($ax_field, 0, $li_lastPos);
			$ls_field = substr($ax_field, $li_lastPos + 1);


			return $ls_prefix . '.' . Inflector::underscore($ls_field);
		}


		return Inflector::underscore($ax_field);
	}
}
