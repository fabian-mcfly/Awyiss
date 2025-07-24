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
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($ls_name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $ls_name, static::class));
		}

		$this->_rules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function addCreate(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($ls_name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $ls_name, static::class));
		}

		$this->_createRules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function addUpdate(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($ls_name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $ls_name, static::class));
		}

		$this->_updateRules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function addDelete(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if ($this->ruleExists($ls_name)) {
			throw new RuntimeException(sprintf('Rule `%s` already exists in `%s`', $ls_name, static::class));
		}

		$this->_deleteRules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function existsIn(array|string $field, BaseTable|Association|string $table, array|string|null $message = null): RuleInvoker {
		$la_options = is_array($message) ? $message : ['message' => $message];
		$ls_message = !empty($la_options['message']) ? $la_options['message'] : __d('validation', 'error_exists_in');
		unset($la_options['message']);

		if (!empty($message['errorField'])) {
			$ls_errorField = $message['errorField'];
		}
		else {
			$ls_errorField = is_string($field) ? $field : current($field);
		}

		$la_fields = $this->unmapFields((array)$field);

		return $this->_addError(new ExistsIn($la_fields, $table, $la_options), '_existsIn', ['errorField' => $ls_errorField, 'message' => $ls_message]);
	}


	/**
	 * @inheritDoc
	 */
	public function isUnique(array $fields, array|string|null $message = null): RuleInvoker {
		$la_options = is_array($message) ? $message : ['message' => $message];
		$ls_message = !empty($la_options['message']) ? $la_options['message'] : __d('validation', 'error_unique');
		unset($la_options['message']);

		if (!empty($message['errorField'])) {
			$ls_errorField = $message['errorField'];
		}
		else {
			$ls_errorField = current($fields);
		}

		$la_fields = $this->unmapFields($fields);

		return $this->_addError(new IsUnique($la_fields, $la_options), '_isUnique', ['errorField' => $ls_errorField, 'message' => $ls_message]);
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
		$ls_message = $message ?: __d('validation', 'error_valid_count', [$operator, $count]);

		return $this->_addError(
			new ValidCount($this->unmapField($field)),
			'_validCount',
			[
				'count' => $count,
				'operator' => $operator,
				'errorField' => $field,
				'message' => $ls_message,
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
		$ls_associationAlias = $association instanceof Association ? $association->getName() : $association;
		$ls_message = $message ?: __d('validation', 'error_link_constraint_rule', $ls_associationAlias);

		return parent::_addLinkConstraintRule($association, $errorField, $ls_message, $linkStatus, $ruleName);
	}


	/**
	 * Returns whether a rule with the given name exists in any of the possible rulesets
	 *
	 * @param string $ruleName
	 * @return bool
	 */
	public function ruleExists(string $ruleName): bool {
		/** @var array<\Cake\Datasource\RuleInvoker> $la_rules */
		$la_rules = array_merge(
			array_keys($this->_rules),
			array_keys($this->_createRules),
			array_keys($this->_updateRules),
			array_keys($this->_deleteRules),
		);

		return in_array($ruleName, $la_rules);
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

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->_options['repository']->getEntityClass() ?? null;
		if ($ls_entityClass) {
			return $ls_entityClass::unmapField($field);
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

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $this->_options['repository']->getEntityClass() ?? null;
		if ($ls_entityClass) {
			return $ls_entityClass::unmapFields($fields);
		}

		return $fields;
	}
}
