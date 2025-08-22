<?php declare(strict_types=1);


namespace Awyiss\Validation;


use Awyiss\Utility\Inflector;
use Cake\Validation\ValidationSet;
use Cake\Validation\Validator as BaseValidator;
use RuntimeException;


/**
 * Extended version that makes use of the `i18nDomain`-property to make
 * validation errors translatable per scope/domain/model.
 * Also transforms all field names to underscored format.
 *
 * @inheritDoc
 */
class Validator extends BaseValidator {
	/**
	 * @var string
	 */
	protected string $i18nDomain = '';


	/**
	 * @return string
	 */
	public function getI18nDomain(): string {
		return $this->i18nDomain;
	}


	/**
	 * @param string $domain
	 * @return static
	 */
	public function setI18nDomain(string $domain): static {
		$this->i18nDomain = Inflector::underscore($domain);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function validate(array $data, bool $newRecord = true): array {
		return parent::validate($this->underscoreFields($data, true), $newRecord);
	}


	/**
	 * @inheritDoc
	 */
	public function allowEmptyFor(string $field, ?int $flags = null, $when = true, ?string $message = null): static {
		return parent::allowEmptyFor($this->underscoreField($field), $flags, $when, $message);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpHierarchyChecksInspection (CakePHP marks the `name` argument as optional` which is not the case)
	 */
	public function field(string $name, ?ValidationSet $validationSet = null): ValidationSet {
		$ls_name = $this->underscoreField($name);

		if (empty($this->_fields[ $ls_name ])) {
			$this->_fields[ $ls_name ] = $validationSet ?? new ValidationSet();

			//Allow an empty string per default. Makes much more sense
			$this->allowEmptyString($name);
		}


		return $this->_fields[ $ls_name ];
	}


	/**
	 * @inheritDoc
	 */
	public function hasField(string $name): bool {
		return isset($this->_fields[ $this->underscoreField($name) ]);
	}


	/**
	 * @inheritDoc
	 */
	public function remove(string $field, ?string $rule = null): static {
		if ($rule === null) {
			unset($this->_fields[ $this->underscoreField($field) ]);
		}
		else {
			$this->field($field)->remove($rule);
		}

		return $this;
	}


	/**
	 * @param string $field
	 * @return string|null
	 */
	public function getRequiredMessage(string $field): ?string {
		$ls_field = $this->underscoreField($field);

		if (!isset($this->_fields[ $ls_field ])) {
			return null;
		}

		if (isset($this->_presenceMessages[ $ls_field ])) {
			return $this->_presenceMessages[ $ls_field ];
		}

		return __df($this->i18nDomain, 'validation', 'error_required');
	}


	/**
	 * @param string $field
	 * @return string|null
	 */
	public function getNotEmptyMessage(string $field): ?string {
		$ls_field = $this->underscoreField($field);

		if (!isset($this->_fields[ $ls_field ])) {
			return null;
		}

		foreach ($this->_fields[ $ls_field ] as $lo_rule) {
			if ($lo_rule->get('rule') === 'notBlank' && $lo_rule->get('message')) {
				return $lo_rule->get('message');
			}
		}

		if (isset($this->_allowEmptyMessages[ $ls_field ])) {
			return $this->_allowEmptyMessages[ $ls_field ];
		}

		return __df($this->i18nDomain, 'validation', 'error_not_empty');
	}


	/**
	 * Re-implemented to translate the passed arguments for the error message.
	 * This way, validation messages from rules like this receive the
	 * translated arguments:
	 * ```
	 * $validator->add('password', [
	 * 	'compareWith' => ['rule' => ['compareWith', 'password_confirm']],
	 * ]);
	 * ```
	 * Result: `This value does not match the field "Confirm password"`
	 *
	 * @param string $field
	 * @param ValidationSet $rules
	 * @param array $data
	 * @param bool $newRecord
	 * @return array
	 */
	protected function _processRules(string $field, ValidationSet $rules, array $data, bool $newRecord): array {
		$la_errors = [];

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

			$lx_param = $lo_rule->get('pass')[0] ?? [];

			if (!$lx_param) {
				$la_errors[ $ls_name ] = __df($this->i18nDomain, 'validation', 'error_' . Inflector::underscore($ls_name));

				if ($lo_rule->isLast()) {
					break;
				}

				continue;
			}

			if (in_array($ls_name, ['equalTo', 'sameAs', 'notSameAs', 'compareWith', 'compareFields'])) {
				$lx_param = __d($this->i18nDomain, Inflector::underscore($lx_param));
			}
			elseif ($ls_name == 'dateTime') {
				$lx_param = is_string($lx_param) ? $lx_param : ($lx_param[0] ?? 'Ymd');
			}
			elseif ($ls_name == 'inList') {
				$lx_param = implode(', ', $lx_param);
			}
			elseif (!is_scalar($lx_param)) {
				throw new RuntimeException(sprintf('Missing translation informations for `%s`, passed arguments: `%s`', $ls_name, print_r($lx_param, true)));
			}

			$la_pass[ $ls_name ] = $lx_param;

			$la_errors[ $ls_name ] = __df($this->i18nDomain, 'validation', 'error_' . Inflector::underscore($ls_name), $la_pass[ $ls_name ] ?? '');

			if ($lo_rule->isLast()) {
				break;
			}
		}


		return $la_errors;
	}


	/**
	 * Transforms the given array so that keys or values (depending on $underscoreKeys)
	 * are written in underscored-format.
	 *
	 * @param array $fields
	 * @param bool $underscoreKeys Whether to transform the keys or the values of the array.
	 * @return array
	 */
	protected function underscoreFields(array $fields, bool $underscoreKeys = false): array {
		$la_fields = [];

		/** @noinspection PhpVariableNamingConventionInspection */
		foreach ($fields as $field => $value) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$mapped = ($underscoreKeys ? 'field' : 'value');
			$$mapped = $this->underscoreField($$mapped);

			$la_fields[ $field ] = $value;
		}

		return $la_fields;
	}


	/**
	 * Transforms the given field to underscored format, but skips
	 * fields that are empty, not a string or start with an underscore.
	 *
	 * If `$field` contains a dot, only the part after the last dot is transformed.
	 *
	 * @param mixed $field
	 * @return mixed
	 */
	protected function underscoreField(mixed $field): mixed {
		if (!$field || !is_string($field) || str_starts_with($field, '_')) {
			return $field;
		}

		$li_lastPos = strrpos($field, '.');
		if ($li_lastPos === false) {
			return Inflector::underscore($field);
		}

		$ls_prefix = substr($field, 0, $li_lastPos);
		$ls_field = substr($field, $li_lastPos + 1);

		return $ls_prefix . '.' . Inflector::underscore($ls_field);
	}
}
