<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
 */

declare(strict_types=1);


namespace Awyiss\ORM;


use Awyiss\Model\Table;
use Cake\Datasource\RuleInvoker;
use Cake\ORM\Association;
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
class RulesChecker extends \Cake\ORM\RulesChecker {
	public function isUnique (array $aa_fields, $ax_options = NULL): RuleInvoker {
		$la_options = is_array($ax_options) ? $ax_options : ['message' => $ax_options];

		if (empty($la_options['message'])) {
			$la_options['message'] = __('validation::error_unique');
		}

		return parent::isUnique($aa_fields, $la_options);
	}


	public function existsIn ($ax_fields, $ax_table, $ax_options = NULL): RuleInvoker {
		$la_options = is_array($ax_options) ? $ax_options : ['message' => $ax_options];

		if (empty($la_options['message'])) {
			$la_options['message'] = __('validation::error_exists_in');
		}

		return parent::existsIn($ax_fields, $ax_table, $la_options);
	}


	protected function _addLinkConstraintRule ($ax_association, ?string $as_errorField, ?string $as_message, string $as_linkStatus, string $as_ruleName): RuleInvoker {
		$ls_errorField = $as_errorField;
		$lx_association = $ax_association;

		if ($lx_association instanceof Association) {
			$ls_associationAlias = $lx_association->getName();

			if ($ls_errorField === NULL) {
				$ls_errorField = $lx_association->getProperty();
			}
		}
		elseif (is_string($lx_association)) {
			$ls_associationAlias = $lx_association;

			if ($ls_errorField === NULL) {
				$repository = $this->_options['repository'] ?? NULL;
				if ($repository instanceof Table) {
					$lx_association = $repository->getAssociation($lx_association);
					$ls_errorField = $lx_association->getProperty();
				}
				else {
					$ls_errorField = Inflector::underscore($lx_association);
				}
			}
		}
		else {
			throw new \InvalidArgumentException(sprintf('Argument 1 is expected to be of type `\Cake\ORM\Association|string`, `%s` given.', getTypeName($lx_association)));
		}

		$ls_message = $as_message;
		if ( ! $ls_message) {
			$ls_message = __('validation::error_link_constraint_rule', $ls_associationAlias);
		}

		return parent::_addLinkConstraintRule($lx_association, $ls_errorField, $ls_message, $as_linkStatus, $as_ruleName);
	}


	public function validCount (string $as_field, int $ai_count = 0, string $as_operator = '>', ?string $as_message = NULL): RuleInvoker {
		$ls_message = $as_message;
		if ( ! $ls_message) {
			$ls_message = __('validation::error_valid_count', [$as_operator, $ai_count]);
		}

		return parent::validCount($as_field, $ai_count, $as_operator, $ls_message);
	}
}