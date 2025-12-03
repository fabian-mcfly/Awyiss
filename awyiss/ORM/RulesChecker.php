<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\ORM;


use Awyiss\ORM\Rule\ExistsIn;
use Cake\Datasource\RuleInvoker;
use Cake\ORM\Association;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\Rule\ValidCount;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\ORM\Table as BaseTable;
use RuntimeException;


/**
 * ORM flavoured rules checker.
 *
 * Adds ORM related features to the RulesChecker class.
 *
 * This variation uses translatable identifiers as error messages
 *
 * @see \Cake\ORM\RulesChecker
 * @see \Cake\Datasource\RulesChecker
 */
class RulesChecker extends BaseRulesChecker {
	/**
	 * @inheritDoc
	 */
	public function add(callable $rule, array|string|null $name = null, array $options = []): static {
		$name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $name, static::class));
		}

		$this->_rules[ $name ] = $this->_addError($rule, $name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function addCreate(callable $rule, array|string|null $name = null, array $options = []): static {
		$name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $name, static::class));
		}

		$this->_createRules[ $name ] = $this->_addError($rule, $name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function addUpdate(callable $rule, array|string|null $name = null, array $options = []): static {
		$name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $name, static::class));
		}

		$this->_updateRules[ $name ] = $this->_addError($rule, $name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function addDelete(callable $rule, array|string|null $name = null, array $options = []): static {
		$name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $name, static::class));
		}

		$this->_deleteRules[ $name ] = $this->_addError($rule, $name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function existsIn(array|string $field, BaseTable|Association|string $table, array|string|null $message = null): RuleInvoker {
		$options = is_array($message) ? $message : ['message' => $message];
		$message = !empty($options['message']) ? $options['message'] : __d('validation', 'error_exists_in');
		unset($options['message']);

		if (!empty($options['errorField'])) {
			$errorField = $options['errorField'];
		}
		else {
			$errorField = is_string($field) ? $field : current($field);
		}

		$fields = $this->unmapFields((array)$field);

		return $this->_addError(new ExistsIn($fields, $table, $options), '_existsIn', ['errorField' => $errorField, 'message' => $message]);
	}


	/**
	 * @inheritDoc
	 */
	public function isUnique(array $fields, array|string|null $message = null): RuleInvoker {
		$options = is_array($message) ? $message : ['message' => $message];
		$message = !empty($options['message']) ? $options['message'] : __d('validation', 'error_unique');
		unset($options['message']);

		if (!empty($options['errorField'])) {
			$errorField = $options['errorField'];
		}
		else {
			$errorField = current($fields);
		}

		$fields = $this->unmapFields($fields);

		return $this->_addError(new IsUnique($fields, $options), '_isUnique', ['errorField' => $errorField, 'message' => $message]);
	}


	/**
	 * @inheritDoc
	 * @param string $field
	 * @param int $count
	 * @param string $operator
	 * @param string|null $message
	 * @return \Cake\Datasource\RuleInvoker
	 */
	public function validCount(string $field, int $count = 0, string $operator = '>', ?string $message = null): RuleInvoker {
		$message = $message ?: __d('validation', 'error_valid_count', [$operator, $count]);

		return $this->_addError(
			new ValidCount($this->unmapField($field)),
			'_validCount',
			[
				'count' => $count,
				'operator' => $operator,
				'errorField' => $field,
				'message' => $message,
			]
		);
	}


	/**
	 * Re-implemented to use a proper fallback error message
	 *
	 * @inheritDoc
	 * @param Association|string $association
	 * @param string|null $errorField
	 * @param string|null $message
	 * @param string $linkStatus
	 * @param string $ruleName
	 * @return \Cake\Datasource\RuleInvoker
	 */
	protected function _addLinkConstraintRule(
		Association|string $association,
		?string $errorField,
		?string $message,
		string $linkStatus,
		string $ruleName
	): RuleInvoker {
		$associationAlias = $association instanceof Association ? $association->getName() : $association;
		$message = $message ?: __d('validation', 'error_link_constraint_rule', $associationAlias);

		return parent::_addLinkConstraintRule($association, $errorField, $message, $linkStatus, $ruleName);
	}


	/**
	 * Returns whether a rule with the given name exists in any of the possible rulesets
	 *
	 * @param string $ruleName
	 * @return bool
	 */
	public function ruleExists(string $ruleName): bool {
		/** @var array<\Cake\Datasource\RuleInvoker> $rules */
		$rules = array_merge(
			array_keys($this->_rules),
			array_keys($this->_createRules),
			array_keys($this->_updateRules),
			array_keys($this->_deleteRules),
		);

		return in_array($ruleName, $rules);
	}


	/**
	 * Extract the name from the options array
	 *
	 * @param array|null $options
	 * @return string
	 */
	protected function extractName(?array $options): string {
		if ($options === null || empty($options['name'])) {
			throw new RuntimeException(sprintf('Missing option `name` in `%s`', static::class));
		}

		return $options['name'];
	}


	/**
	 * @param string $field
	 * @return string
	 */
	protected function unmapField(string $field): string {
		if (!($this->_options['repository'] ?? null)) {
			return $field;
		}

		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $this->_options['repository']->getEntityClass() ?? null;
		if ($entityClass) {
			return $entityClass::unmapField($field);
		}

		return $field;
	}


	/**
	 * @param array $fields
	 * @return array
	 */
	protected function unmapFields(array $fields): array {
		if (!($this->_options['repository'] ?? null)) {
			return $fields;
		}

		/** @var \Awyiss\Model\Entity $entityClass */
		$entityClass = $this->_options['repository']->getEntityClass() ?? null;
		if ($entityClass) {
			return $entityClass::unmapFields($fields);
		}

		return $fields;
	}
}
