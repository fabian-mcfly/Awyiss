<?php declare(strict_types=1);


namespace Awyiss\Validation;


use Cake\Utility\Inflector;
use Cake\Validation\ValidationSet;
use Cake\Validation\Validator as BaseValidator;
use RuntimeException;


/**
 * {@inheritDoc}
 *
 * Extended version that makes use of the `i18nDomain`-property to make validation errors translatable
 * per scope/domain/model.
 */
class Validator extends BaseValidator {
	protected string $i18nDomain = '';


	/**
	 * @param string $domain
	 * @return void
	 */
	public function setI18nDomain(string $domain): void {
		$this->i18nDomain = Inflector::underscore($domain);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validate(array $data, bool $newRecord = true): array {
		$la_data = $this->underscoreFields($data, true);


		return parent::validate($la_data, $newRecord);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function allowEmptyFor(string $field, ?int $flags = null, $when = true, ?string $message = null): static {
		$ls_field = $this->underscoreField($field);


		return parent::allowEmptyFor($ls_field, $flags, $when, $message);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpHierarchyChecksInspection <-- WHY?
	 */
	public function field(string $name, ?ValidationSet $validationSet = null): ValidationSet {
		$ls_name = $this->underscoreField($name);

		if (empty($this->_fields[ $ls_name ])) {
			$lo_validationSet = $validationSet ?: new ValidationSet();
			$this->_fields[ $ls_name ] = $lo_validationSet;

			//Allow an empty string per default. Makes much more sense
			$this->allowEmptyString($name);
		}


		return $this->_fields[ $ls_name ];
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function hasField(string $name): bool {
		$ls_name = $this->underscoreField($name);


		return isset($this->_fields[ $ls_name ]);
	}


	/**
	 * @param string $field
	 * @return string|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getRequiredMessage(string $field): ?string {
		$ls_field = $this->underscoreField($field);

		if (!isset($this->_fields[ $ls_field ])) {
			return null;
		}

		$ls_defaultMessage = __dfx($this->i18nDomain, 'validation', $field, 'error_required');


		return $this->_presenceMessages[ $ls_field ] ?? $ls_defaultMessage;
	}


	/**
	 * @param string $field
	 * @return string|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getNotEmptyMessage(string $field): ?string {
		$ls_field = $this->underscoreField($field);

		if (!isset($this->_fields[ $ls_field ])) {
			return null;
		}

		$ls_defaultMessage = __dfx($this->i18nDomain, 'validation', $field, 'error_not_empty');

		foreach ($this->_fields[ $ls_field ] as $lo_rule) {
			if ($lo_rule->get('rule') === 'notBlank' && $lo_rule->get('message')) {
				return $lo_rule->get('message');
			}
		}


		return $this->_allowEmptyMessages[ $ls_field ] ?? $ls_defaultMessage;
	}


	/**
	 * @param string $field
	 * @param ValidationSet $rules
	 * @param array $data
	 * @param bool $newRecord
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _processRules(string $field, ValidationSet $rules, array $data, bool $newRecord): array {
		$la_errors = [];
		// Loading default provider in case there is none
		$this->getProvider('default');

		foreach ($rules as $ls_name => $lo_rule) {
			$lx_result = $lo_rule->process($data[ $field ], $this->_providers, [
				'newRecord' => $newRecord,
				'data' => $data,
				'field' => $field,
			]);

			if ($lx_result === true) {
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
				'field' => __df($this->i18nDomain, 'system', Inflector::underscore($field)),
			];

			$lx_pass = $lo_rule->get('pass')[0] ?? [];
			if ($lx_pass) {
				if (in_array($ls_name, ['equalTo', 'sameAs', 'notSameAs', 'compareWith', 'compareFields'])) {
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
					throw new RuntimeException(sprintf('Missing translation informations for `%s`, passed arguments: `%s`', $ls_name, print_r($lx_pass, true)));
				}
				else {
					dd($ls_name, $lx_pass, $lo_rule->get('pass'), __FILE__, __LINE__);
				}
				$la_pass[ $ls_name ] = $lx_pass;
			}

			$la_errors[ $ls_name ] = __dfx($this->i18nDomain, 'validation', $ls_name, 'error_' . Inflector::underscore($ls_name), $la_pass);

			if ($lo_rule->isLast()) {
				break;
			}
		}


		return $la_errors;
	}


	/**
	 * Transforms the given array so that keys or values (depending on $variableKey)
	 * are written in camelBacked-format.
	 *
	 * @param array $fields
	 * @param bool $variableKey
	 * @return array
	 */
	protected function underscoreFields(array $fields, bool $underscoreKeys = false): array {
		$la_fields = [];

		foreach ($fields as $lx_field => $lx_value) {
			$lx_mapped = ($underscoreKeys ? 'lx_field' : 'lx_value');
			$$lx_mapped = $this->underscoreField($$lx_mapped);

			$la_fields[ $lx_field ] = $lx_value;
		}


		return $la_fields;
	}


	/**
	 * @param mixed $field
	 * @return mixed
	 */
	protected function underscoreField(mixed $field): mixed {
		if (!$field || !is_string($field) || str_starts_with($field, '_')) {
			return $field;
		}

		$li_lastPos = strrpos($field, '.');
		if ($li_lastPos !== false) {
			$ls_prefix = substr($field, 0, $li_lastPos);
			$ls_field = substr($field, $li_lastPos + 1);


			return $ls_prefix . '.' . Inflector::underscore($ls_field);
		}


		return Inflector::underscore($field);
	}
}
