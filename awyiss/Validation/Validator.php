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
		$underscoredName = $this->underscoreField($name);

		if (empty($this->_fields[ $underscoredName ])) {
			$this->_fields[ $underscoredName ] = $validationSet ?? new ValidationSet();

			//Allow an empty string per default. Makes much more sense
			$this->allowEmptyString($name);
		}


		return $this->_fields[ $underscoredName ];
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
		$field = $this->underscoreField($field);

		if (!isset($this->_fields[ $field ])) {
			return null;
		}

		if (isset($this->_presenceMessages[ $field ])) {
			return $this->_presenceMessages[ $field ];
		}

		return __df($this->i18nDomain, 'validation', 'error_required');
	}


	/**
	 * @param string $field
	 * @return string|null
	 */
	public function getNotEmptyMessage(string $field): ?string {
		$field = $this->underscoreField($field);

		if (!isset($this->_fields[ $field ])) {
			return null;
		}

		foreach ($this->_fields[ $field ] as $rule) {
			if ($rule->get('rule') === 'notBlank' && $rule->get('message')) {
				return $rule->get('message');
			}
		}

		if (isset($this->_allowEmptyMessages[ $field ])) {
			return $this->_allowEmptyMessages[ $field ];
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
		$errors = [];

		foreach ($rules as $name => $rule) {
			$result = $rule->process($data[ $field ], $this->_providers, [
				'newRecord' => $newRecord,
				'data' => $data,
				'field' => $field,
			]);

			if ($result === true) {
				continue;
			}

			if (is_string($result) || (is_array($result) && $name === static::NESTED)) {
				if (is_string($result)) {
					$errors[ $name ] = $result;
				}
				else {
					$errors = $result;
				}

				if ($rule->isLast()) {
					break;
				}

				continue;
			}

			$pass = [
				'field' => __df($this->i18nDomain, 'system', Inflector::underscore($field)),
			];

			$param = $rule->get('pass')[0] ?? [];

			if (!$param) {
				$errors[ $name ] = __df($this->i18nDomain, 'validation', 'error_' . Inflector::underscore($name));

				if ($rule->isLast()) {
					break;
				}

				continue;
			}

			if (in_array($name, ['equalTo', 'sameAs', 'notSameAs', 'compareWith', 'compareFields'])) {
				$param = __d($this->i18nDomain, Inflector::underscore($param));
			}
			elseif ($name == 'dateTime') {
				$param = is_string($param) ? $param : ($param[0] ?? 'Ymd');
			}
			elseif ($name == 'enum') {
				$cases = array_map(fn ($case) => $case->value, $param::cases());
				$param = '`' . implode('`, `', $cases) . '`';
			}
			elseif ($name == 'inList') {
				$param = implode(', ', $param);
			}
			elseif (!is_scalar($param)) {
				throw new RuntimeException(sprintf('Missing translation informations for `%s`, passed arguments: `%s`', $name, print_r($param, true)));
			}

			$pass[ $name ] = $param;

			$errors[ $name ] = __df($this->i18nDomain, 'validation', 'error_' . Inflector::underscore($name), $pass[ $name ] ?? '');

			if ($rule->isLast()) {
				break;
			}
		}


		return $errors;
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
		$underscoredFields = [];

		foreach ($fields as $field => $value) {
			$mapped = ($underscoreKeys ? 'field' : 'value');
			$$mapped = $this->underscoreField($$mapped);

			$underscoredFields[ $field ] = $value;
		}

		return $underscoredFields;
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

		$lastPos = strrpos($field, '.');
		if ($lastPos === false) {
			return Inflector::underscore($field);
		}

		$prefix = substr($field, 0, $lastPos);
		$field = substr($field, $lastPos + 1);

		return $prefix . '.' . Inflector::underscore($field);
	}
}
