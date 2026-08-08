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
		$this->i18nDomain = Inflector::camelize($domain);

		return $this;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpHierarchyChecksInspection (CakePHP marks the `name` argument as optional` which is not the case)
	 */
	public function field(string $name, ?ValidationSet $validationSet = null): ValidationSet {
		if (!isset($this->_fields[ $name ])) {
			$this->_fields[ $name ] = $validationSet ?? new ValidationSet();

			//Allow an empty string per default. Makes much more sense
			$this->allowEmptyString($name);
		}

		return $this->_fields[ $name ];
	}


	/**
	 * @param string $field
	 * @return string|null
	 */
	public function getRequiredMessage(string $field): ?string {
		if (!isset($this->_fields[ $field ])) {
			return null;
		}

		if (isset($this->_presenceMessages[ $field ])) {
			return $this->_presenceMessages[ $field ];
		}

		return __df($this->i18nDomain, 'Validation', 'error_required');
	}


	/**
	 * @param string $field
	 * @return string|null
	 */
	public function getNotEmptyMessage(string $field): ?string {
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

		return __df($this->i18nDomain, 'Validation', 'error_not_empty');
	}


	/**
	 * Re-implemented to translate the passed arguments for the error message.
	 * This way, validation messages from rules like this receive the
	 * translated arguments:
	 * ```
	 * $validator->add('password', [
	 * 	'compareWith' => ['rule' => ['compareWith', 'passwordConfirm']],
	 * ]);
	 * ```
	 * Result: `This value does not match the field "Confirm password"`
	 *
	 * @param string $field
	 * @param \Cake\Validation\ValidationSet $rules
	 * @param array $data
	 * @param bool $newRecord
	 * @param array $context
	 * @return array
	 */
	protected function _processRules(string $field, ValidationSet $rules, array $data, bool $newRecord, array $context = []): array {
		$errors = [];
		$context = compact('newRecord', 'data', 'field') + $context;

		foreach ($rules as $name => $rule) {
			$result = $rule->process($data[ $field ], $this->_providers, $context);

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
				'field' => __df($this->i18nDomain, 'System', Inflector::underscore($field)),
			];

			$param = $rule->get('pass')[0] ?? [];

			if (!$param) {
				$errors[ $name ] = __df($this->i18nDomain, 'Validation', 'error_' . Inflector::underscore($name));

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

			$errors[ $name ] = __df($this->i18nDomain, 'Validation', 'error_' . Inflector::underscore($name), $pass[ $name ] ?? '');

			if ($rule->isLast()) {
				break;
			}
		}


		return $errors;
	}
}
