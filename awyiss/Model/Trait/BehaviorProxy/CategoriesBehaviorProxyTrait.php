<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query\SelectQuery;


/**
 * Proxy methods for CategoriesBehavior
 */
trait CategoriesBehaviorProxyTrait {
	/**
	 * Add category-related conditions to a given query.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param mixed $selectedCategory
	 * @param bool $sortAggregation
	 * @return \Cake\ORM\Query\SelectQuery
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::filterQuery()
	 */
	public function filterQuery(SelectQuery $query, mixed $selectedCategory = null, bool $sortAggregation = true): SelectQuery {
		return $this->getBehavior('Categories')->filterQuery($query, $selectedCategory, $sortAggregation);
	}


	/**
	 * Get the categories for this table.
	 *
	 * @param bool $returnRaw
	 * @return \Cake\Datasource\ResultSetInterface|\Cake\Collection\Iterator\TreeIterator|array|null
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getCategories()
	 */
	public function getCategories(bool $returnRaw = false): ResultSetInterface|TreeIterator|array|null {
		return $this->getBehavior('Categories')->getCategories($returnRaw);
	}


	/**
	 * Get the query conditions for the selected category.
	 *
	 * @param mixed $selectedCategory
	 * @return array
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::getQueryConditions()
	 */
	public function getQueryConditions(mixed $selectedCategory = null): array {
		return $this->getBehavior('Categories')->getQueryConditions($selectedCategory);
	}


	/**
	 * Group the result by category.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string|null $column
	 * @param string|null $associationName
	 * @param bool $sortByAssociation
	 * @return \Cake\ORM\Query\SelectQuery
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::groupResult()
	 */
	public function groupResult(
		SelectQuery $query,
		?string $column = null,
		?string $associationName = null,
		bool $sortByAssociation = true
	): SelectQuery {
		return $this->getBehavior('Categories')->groupResult($query, $column, $associationName, $sortByAssociation);
	}


	/**
	 * Sort the query by category.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string|null $column
	 * @param string|null $associationName
	 * @return \Cake\ORM\Query\SelectQuery
	 * @see \Awyiss\Model\Behavior\CategoriesBehavior::sortQuery()
	 */
	public function sortQuery(SelectQuery $query, ?string $column = null, ?string $associationName = null): SelectQuery {
		return $this->getBehavior('Categories')->sortQuery($query, $column, $associationName);
	}
}
