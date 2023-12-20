<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
 */


declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\Model\Table;
use Awyiss\ORM\Rule\ExistsIn;
use Cake\Datasource\RuleInvoker;
use Cake\ORM\Association;
use Cake\ORM\Rule\LinkConstraint;
use Cake\Utility\Inflector;
use InvalidArgumentException;


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
class RulesChecker extends \Cake\ORM\RulesChecker {
	/**
	 * @inheritDoc
	 *
	 * @param array $aa_fields
	 * @param $ax_options
	 *
	 * @return RuleInvoker
	 */
	public function isUnique(array $aa_fields, $ax_options = NULL): RuleInvoker {
		$la_options = is_array($ax_options) ? $ax_options : ['message' => $ax_options];

		if (empty($la_options['message'])) {
			$la_options['message'] = __d('validation', 'error_unique');
		}

		$la_fields = $aa_fields;
		if (($this->_options['repository'] ?? NULL) && $ls_entityClass = ($this->_options['repository']->getEntityClass() ?? NULL)) {
			$la_fields = $ls_entityClass::unmapFields($la_fields);
		}


		return parent::isUnique($la_fields, $la_options);
	}


	/**
	 * @inheritDoc
	 *
	 * @param $ax_fields
	 * @param $ax_table
	 * @param $ax_options
	 *
	 * @return RuleInvoker
	 */
	public function existsIn($ax_fields, $ax_table, $ax_options = NULL): RuleInvoker {
		$la_options = is_array($ax_options) ? $ax_options : ['message' => $ax_options];
		$ls_message = ($la_options['message'] ?? NULL) ?: __d('validation', 'error_exists_in');
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
	 *
	 * @param string $as_field
	 * @param int $ai_count
	 * @param string $as_operator
	 * @param null|string $as_message
	 *
	 * @return RuleInvoker
	 */
	public function validCount(string $as_field, int $ai_count = 0, string $as_operator = '>', ?string $as_message = NULL): RuleInvoker {
		$ls_message = $as_message;
		if (!$ls_message) {
			$ls_message = __d('validation', 'error_valid_count', [$as_operator, $ai_count]);
		}


		return parent::validCount($as_field, $ai_count, $as_operator, $ls_message);
	}


	/**
	 * @inheritDoc
	 *
	 * Re-implemented 1:1 to use a proper fallback error message
	 *
	 * @param $ax_association
	 * @param null|string $as_errorField
	 * @param null|string $as_message
	 * @param string $as_linkStatus
	 * @param string $as_ruleName
	 *
	 * @return RuleInvoker
	 */
	protected function _addLinkConstraintRule(Association|string $ax_association, ?string $as_errorField, ?string $as_message, string $as_linkStatus, string $as_ruleName): RuleInvoker {
		$ls_errorField = $as_errorField;
		if ($ax_association instanceof Association) {
			$ls_associationAlias = $ax_association->getName();
			$ls_errorField ??= $ax_association->getProperty();
		}
		else {
			$ls_associationAlias = $ax_association;

			if ($ls_errorField === NULL) {
				$repository = $this->_options['repository'] ?? NULL;
				if ($repository instanceof Table) {
					$ax_association = $repository->getAssociation($ax_association);
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
