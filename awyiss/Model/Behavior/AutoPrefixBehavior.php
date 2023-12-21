<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;


/**
 * This behavior automatically prefixes database columns with the name of their table.
 * This makes writing out the table name obsolete when using joins.
 */
class AutoPrefixBehavior extends Behavior {
	protected array $_defaultConfig = [
		'enabled' => true,
		'implementedEvents' => [
			'beforeFind',
		],
	];
	protected string $alias;


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
		$this->alias = $this->table()->getAlias();
	}


	/**
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @param bool $ab_primary
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options, bool $ab_primary): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//For all parts of the query, call `expressionVisitor`
		$ao_query->traverseParts(function (?QueryExpression $ao_expression): void {
			if (is_null($ao_expression)) {
				return;
			}

			$this->expressionVisitor($ao_expression);
		}, ['where']);
	}


	/**
	 * @param QueryExpression|UnaryExpression $ao_expression
	 * @return void
	 */
	protected function expressionVisitor(QueryExpression|UnaryExpression $ao_expression): void {
		/**
		 * An expression object that represents an expression with only a single operand.
		 *
		 * @see UnaryExpression
		 */
		if ($ao_expression instanceof UnaryExpression) {
			//Traverse all parts of this expression
			$ao_expression->traverse(function (IdentifierExpression|QueryExpression|ComparisonExpression $ao_expression) {
				//If the expression is an instance of ComparisonExpression, set the prefixed field if it does not contain '.'
				if ($ao_expression instanceof ComparisonExpression) {
					$ls_field = $ao_expression->getField();
					if (!str_contains($ls_field, '.')) {
						$ao_expression->setField($this->alias . '.' . $ls_field);
					}
				}
				//If the expression is an instance of IdentifierExpression, set the prefixed identifier if it does not contain '.'
				elseif ($ao_expression instanceof IdentifierExpression) {
					$ls_field = $ao_expression->getIdentifier();
					if (!str_contains($ls_field, '.')) {
						$ao_expression->setIdentifier($this->alias . '.' . $ls_field);
					}
				}


				//Return the modified expression
				return $ao_expression;
			});


			//That's all there is to do for this type of expression
			return;
		}

		//The expression is an instance of QueryExpression. So iterate all parts
		$ao_expression->iterateParts(function (ExpressionInterface $ao_expression) {
			//If the expression is an instance of ComparisonExpression, set the prefixed field if it does not contain '.'
			if ($ao_expression instanceof ComparisonExpression) {
				$la_field = $ao_expression->getField();
				if (!is_array($la_field)) {
					$la_field = [$la_field];
				}

				foreach ($la_field as $ls_field) {
					if (!str_contains($ls_field, '.')) {
						$ao_expression->setField($this->alias . '.' . $ls_field);
					}
				}
			}
			/*
			 * If the expression is an instance of either QueryExpression or UnaryExpression,
			 * call expressionVisitor again with this sub-expression. Kind of a recursive function here.
			 */
			elseif ($ao_expression instanceof QueryExpression || $ao_expression instanceof UnaryExpression) {
				$this->expressionVisitor($ao_expression);
			}
			else {
				dd($ao_expression, __FILE__, __LINE__);
			}


			return $ao_expression;
		});
	}
}
