<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\ORM\Rule\ExistsIn;
use Cake\Datasource\RuleInvoker;
use Cake\ORM\Association;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\ORM\Table as BaseTable;
use Cake\Utility\Hash;
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
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function add(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if (isset($this->_rules[ $ls_name ])) {
			throw new RuntimeException(sprintf('Cannot redefine rule name. `%s` already exists.', $ls_name));
		}

		$this->_rules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);


		return $this;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function addCreate(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if (isset($this->_createRules[ $ls_name ]) || isset($this->_rules[ $ls_name ])) {
			throw new RuntimeException(sprintf('Cannot redefine create rule name. `%s` already exists.', $ls_name));
		}

		$this->_createRules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);


		return $this;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function addUpdate(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if (isset($this->_updateRules[ $ls_name ]) || isset($this->_rules[ $ls_name ])) {
			throw new RuntimeException(sprintf('Cannot redefine update rule name. `%s` already exists.', $ls_name));
		}

		$this->_updateRules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);


		return $this;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function addDelete(callable $rule, array|string|null $name = null, array $options = []): static {
		$ls_name = is_string($name) ? $name : $this->extractName($name);

		if (isset($this->_deleteRules[ $ls_name ])) {
			throw new RuntimeException(sprintf('Cannot redefine delete rule name. `%s` already exists.', $ls_name));
		}

		$this->_deleteRules[ $ls_name ] = $this->_addError($rule, $ls_name, $options);


		return $this;
	}


	/**
	 * Returns whether a rule with the given name exists in any of the possible rulesets
	 *
	 * @param string $ruleName
	 * @return bool
	 */
	public function exists(string $ruleName): bool {
		/** @var array<\Cake\Datasource\RuleInvoker> $la_rules */
		$la_rules = Hash::merge(
			$this->_rules,
			$this->_createRules,
			$this->_updateRules,
			$this->_deleteRules,
		);

		return array_key_exists($ruleName, $la_rules);
	}


	/**
	 * @inheritDoc
	 * @param array|string $fields
	 * @param BaseTable|Association|string $table
	 * @param array|string|null $options
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function existsIn(array|string $fields, BaseTable|Association|string $table, array|string|null $options = null): RuleInvoker {
		$la_options = is_array($options) ? $options : ['message' => $options];
		$ls_message = $la_options['message'] ?? null ?: __d('validation', 'error_exists_in');
		unset($la_options['message']);

		if (!empty($options['errorField'])) {
			$ls_errorField = $options['errorField'];
		}
		else {
			$ls_errorField = is_string($fields) ? $fields : current($fields);
		}


		$la_fields = (array)$fields;
		if (($this->_options['repository'] ?? null)) {
			$ls_entityClass = $this->_options['repository']->getEntityClass() ?? null;
			if ($ls_entityClass) {
				$la_fields = $ls_entityClass::unmapFields($la_fields);
			}
		}


		return $this->_addError(new ExistsIn($la_fields, $table, $la_options), '_existsIn', ['errorField' => $ls_errorField, 'message' => $ls_message]);
	}


	/**
	 * @inheritDoc
	 * @param array $fields
	 * @param array|string|null $options
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function isUnique(array $fields, array|string|null $options = null): RuleInvoker {
		$la_options = is_array($options) ? $options : ['message' => $options];

		$la_fields = $fields;
		if (($this->_options['repository'] ?? null)) {
			$ls_entityClass = $this->_options['repository']->getEntityClass() ?? null;
			if ($ls_entityClass) {
				$la_fields = $ls_entityClass::unmapFields($la_fields);
			}
		}

		if (empty($la_options['errorField'])) {
			$la_options['errorField'] = current($la_fields);
		}

		if (empty($la_options['message'])) {
			$la_options['message'] = __d('validation', 'error_unique');
		}

		return $this->_addError(new IsUnique($la_fields, $la_options), '_isUnique', $la_options);
	}


	/**
	 * @inheritDoc
	 * @param string $field
	 * @param int $count
	 * @param string $operator
	 * @param string|null $message
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validCount(string $field, int $count = 0, string $operator = '>', ?string $message = null): RuleInvoker {
		$ls_message = $message;
		if (!$ls_message) {
			$ls_message = __d('validation', 'error_valid_count', [$operator, $count]);
		}


		return parent::validCount($field, $count, $operator, $ls_message);
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
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _addLinkConstraintRule(
		Association|string $association,
		?string $errorField,
		?string $message,
		string $linkStatus,
		string $ruleName
	): RuleInvoker {
		if ($association instanceof Association) {
			$ls_associationAlias = $association->getName();
		}
		else {
			$ls_associationAlias = $association;
		}

		$ls_message = $message;
		if (!$ls_message) {
			$ls_message = __d('validation', 'error_link_constraint_rule', $ls_associationAlias);
		}


		return parent::_addLinkConstraintRule($association, $errorField, $ls_message, $linkStatus, $ruleName);
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
}
