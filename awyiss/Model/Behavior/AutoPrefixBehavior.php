<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Awyiss\ORM\Behavior;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\UnaryExpression;
use Cake\Database\ExpressionInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;


class AutoPrefixBehavior extends Behavior {
	protected $_defaultConfig = [
		'enabled' => TRUE,
		'implementedEvents' => [
			'Model.beforeFind' => 'beforeFind',
		]
	];
	protected string $alias;


	public function initialize (array $config): void {
		$this->alias = $this->table()->getAlias();
	}


	public function beforeFind (EventInterface $ao_event, Query $ao_query, \ArrayObject $ao_options, $ab_primary): void {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$ao_query->traverseParts(function(?QueryExpression $ao_expression) {
			if (is_null($ao_expression)) return;

			$this->expressionVisitor($ao_expression);
		}, ['where']);
	}


	protected function expressionVisitor (QueryExpression|UnaryExpression $ao_expression): void {
		if ($ao_expression instanceof UnaryExpression) {
			$ao_expression->traverse(function(IdentifierExpression|QueryExpression|ComparisonExpression $ao_expression) {
				if ($ao_expression instanceof ComparisonExpression) {
					$ls_field = $ao_expression->getField();
					if (strpos($ls_field, '.') === FALSE) {
						$ao_expression->setField($this->alias . '.' . $ls_field);
					}
				}
				elseif ($ao_expression instanceof IdentifierExpression) {
					$ls_field = $ao_expression->getIdentifier();
					if (strpos($ls_field, '.') === FALSE) {
						$ao_expression->setIdentifier($this->alias . '.' . $ls_field);
					}
				}

				return $ao_expression;
			});

			return;
		}

		$ao_expression->iterateParts(function(ExpressionInterface $ao_expression) {
			if ($ao_expression instanceof ComparisonExpression) {
				$ls_field = $ao_expression->getField();
				if (strpos($ls_field, '.') === FALSE) {
					$ao_expression->setField($this->alias . '.' . $ls_field);
				}
			}
			elseif ($ao_expression instanceof QueryExpression) {
				$this->expressionVisitor($ao_expression);
			}
			elseif ($ao_expression instanceof UnaryExpression) {
				$this->expressionVisitor($ao_expression);
			}
			else {
				dd($ao_expression);
			}

			return $ao_expression;
		});
	}
}