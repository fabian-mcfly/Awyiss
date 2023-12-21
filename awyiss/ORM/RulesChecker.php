<?php declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\Model\Table;
use Awyiss\ORM\Rule\ExistsIn;
use Cake\Datasource\RuleInvoker;
use Cake\ORM\Association;
use Cake\ORM\Rule\LinkConstraint;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\ORM\Table as BaseTable;
use Cake\Utility\Inflector;


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
	 * @param array $aa_fields
	 * @param array|string|null $ax_options
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function isUnique(array $aa_fields, array|string|null $ax_options = null): RuleInvoker {
		$la_options = is_array($ax_options) ? $ax_options : ['message' => $ax_options];

		if (empty($la_options['message'])) {
			$la_options['message'] = __d('validation', 'error_unique');
		}

		$la_fields = $aa_fields;
		if (($this->_options['repository'] ?? null)) {
			$ls_entityClass = $this->_options['repository']->getEntityClass() ?? null;
			if ($ls_entityClass) {
				$la_fields = $ls_entityClass::unmapFields($la_fields);
			}
		}


		return parent::isUnique($la_fields, $la_options);
	}


	/**
	 * @inheritDoc
	 * @param array|string $ax_fields
	 * @param BaseTable|Association|string $ax_table
	 * @param array|string|null $ax_options
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function existsIn(array|string $ax_fields, BaseTable|Association|string $ax_table, array|string|null $ax_options = null): RuleInvoker {
		$la_options = is_array($ax_options) ? $ax_options : ['message' => $ax_options];
		$ls_message = $la_options['message'] ?? null ?: __d('validation', 'error_exists_in');
		unset($la_options['message']);

		if (!empty($ax_options['errorField'])) {
			$ls_errorField = $ax_options['errorField'];
		}
		else {
			$ls_errorField = is_string($ax_fields) ? $ax_fields : current($ax_fields);
		}


		//return parent::existsIn($ax_fields, $ax_table, $la_options);
		return $this->_addError(new ExistsIn($ax_fields, $ax_table, $la_options), '_existsIn', ['errorField' => $ls_errorField, 'message' => $ls_message]);
	}


	/**
	 * @inheritDoc
	 * @param string $as_field
	 * @param int $ai_count
	 * @param string $as_operator
	 * @param string|null $as_message
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validCount(string $as_field, int $ai_count = 0, string $as_operator = '>', ?string $as_message = null): RuleInvoker {
		$ls_message = $as_message;
		if (!$ls_message) {
			$ls_message = __d('validation', 'error_valid_count', [$as_operator, $ai_count]);
		}


		return parent::validCount($as_field, $ai_count, $as_operator, $ls_message);
	}


	/**
	 * @inheritDoc
	 * Re-implemented 1:1 to use a proper fallback error message
	 * @param Association|string $ax_association
	 * @param string|null $as_errorField
	 * @param string|null $as_message
	 * @param string $as_linkStatus
	 * @param string $as_ruleName
	 * @return RuleInvoker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _addLinkConstraintRule(
		Association|string $ax_association,
		?string $as_errorField,
		?string $as_message,
		string $as_linkStatus,
		string $as_ruleName
	): RuleInvoker {
		$ls_errorField = $as_errorField;
		if ($ax_association instanceof Association) {
			$ls_associationAlias = $ax_association->getName();
			$ls_errorField ??= $ax_association->getProperty();
		}
		else {
			$ls_associationAlias = $ax_association;

			if ($ls_errorField === null) {
				$lo_repository = $this->_options['repository'] ?? null;
				if ($lo_repository instanceof Table) {
					$ax_association = $lo_repository->getAssociation($ax_association);
					$ls_errorField = $ax_association->getProperty();
				}
				else {
					$ls_errorField = Inflector::underscore($ax_association);
				}
			}
		}

		$ls_message = $as_message;
		if (!$ls_message) {
			$ls_message = __d('validation', 'error_link_constraint_rule', $ls_associationAlias);
		}

		$lo_rule = new LinkConstraint($ax_association, $as_linkStatus);


		return $this->_addError($lo_rule, $as_ruleName, ['errorField' => $ls_errorField, 'message' => $ls_message]);
	}
}
