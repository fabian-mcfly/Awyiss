<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use Cake\Database\Expression\CaseStatementExpression;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderClauseExpression;
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
	protected array $_defaultConfig = [ // phpcs:ignore
		'enabled' => true,
		'implementedEvents' => [
			'beforeFind',
		],
	];
	protected string $alias;


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $config The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->alias = $this->table()->getAlias();
	}


	/**
	 * @param EventInterface $event
	 * @param SelectQuery $query
	 * @param ArrayObject $options
	 * @param bool $primary
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		//For all parts of the query, call `expressionVisitor`
		$query->traverseParts(function (?QueryExpression $expression, string $clause): void {
			if (is_null($expression)) {
				return;
			}

			$this->expressionVisitor($expression);
		}, ['where', 'order']);
	}


	/**
	 * @param QueryExpression|UnaryExpression $expression
	 * @return void
	 */
	protected function expressionVisitor(QueryExpression|UnaryExpression $expression): void {
		/**
		 * An expression object that represents an expression with only a single operand.
		 *
		 * @see UnaryExpression
		 */
		if ($expression instanceof UnaryExpression) {
			//Traverse all parts of this expression
			$expression->traverse(function (IdentifierExpression|QueryExpression|ComparisonExpression $expression) {
				//If the expression is an instance of ComparisonExpression, set the prefixed field if it does not contain '.'
				if ($expression instanceof ComparisonExpression) {
					$field = $expression->getField();
					if (!str_contains($field, '.')) {
						$expression->setField($this->alias . '.' . $field);
					}
				}
				//If the expression is an instance of IdentifierExpression, set the prefixed identifier if it does not contain '.'
				elseif ($expression instanceof IdentifierExpression) {
					$field = $expression->getIdentifier();
					if (!str_contains($field, '.')) {
						$expression->setIdentifier($this->alias . '.' . $field);
					}
				}


				//Return the modified expression
				return $expression;
			});


			//That's all there is to do for this type of expression
			return;
		}

		//The expression is an instance of QueryExpression. So iterate all parts
		$expression->iterateParts(function (ExpressionInterface|string $expression) {
			if (is_string($expression)) {
				return $expression;
			}

			//If the expression is an instance of ComparisonExpression, set the prefixed field if it does not contain '.'
			if ($expression instanceof ComparisonExpression) {
				$fields = $expression->getField();
				if (!is_array($fields)) {
					$fields = [$fields];
				}

				foreach ($fields as $field) {
					if (!is_string($field)) {
						continue;
					}

					if (!str_contains($field, '.')) {
						$expression->setField($this->alias . '.' . $field);
					}
				}

				return $expression;
			}
			/*
			 * If the expression is an instance of either QueryExpression or UnaryExpression,
			 * call expressionVisitor again with this sub-expression. Kind of a recursive function here.
			 */
			if ($expression instanceof QueryExpression || $expression instanceof UnaryExpression) {
				$this->expressionVisitor($expression);

				return $expression;
			}

			if ($expression instanceof OrderClauseExpression) {
				$field = $expression->getField();
				if (is_string($field) && !str_contains($field, '.')) {
					$expression->setField($this->alias . '.' . $field);
				}

				return $expression;
			}

			if ($expression instanceof CaseStatementExpression) {
				// Skip CaseStatementExpression for now
				return $expression;
			}

			dump($expression, __FILE__, __LINE__);
			exit;
		});
	}
}
