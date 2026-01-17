<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Cake\ORM\Query\SelectQuery;


/**
 * Proxy methods for SearchBehavior
 */
trait SearchBehaviorProxyTrait {
	/**
	 * Get possible field values for the search form.
	 *
	 * @param string $column
	 * @param string|null $type
	 * @return array|null
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function getPossibleFieldValues(string $column, ?string $type = null): ?array {
		return $this->getBehavior('Search')->getPossibleFieldValues($column, $type);
	}


	/**
	 * Get filter columns for the search form.
	 *
	 * @param array $blocklistedColumns
	 * @param array|null $selectedOperators
	 * @param array|null $selectedValues
	 * @param bool $includePossibleValues
	 * @return array<string, \Awyiss\Model\Behavior\Search\FilterColumnSettings>
	 * @see \Awyiss\Model\Behavior\SearchBehavior::getFilterColumns()
	 */
	public function getFilterColumns(array $blocklistedColumns = [], ?array $selectedOperators = null, ?array $selectedValues = null, bool $includePossibleValues = true): array {
		return $this->getBehavior('Search')->getFilterColumns(
			$blocklistedColumns,
			$selectedOperators,
			$selectedValues,
			$includePossibleValues
		);
	}


	/**
	 * Apply search filters to a query.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array|null $filterColumns
	 * @return \Cake\ORM\Query\SelectQuery
	 * @see \Awyiss\Model\Behavior\SearchBehavior::filterQuery()
	 */
	public function searchFilterQuery(SelectQuery $query, ?array $filterColumns = null): SelectQuery {
		return $this->getBehavior('Search')->filterQuery($query, $filterColumns);
	}


	/**
	 * Normalize column type.
	 *
	 * @param string $type
	 * @return string
	 * @see \Awyiss\Model\Behavior\SearchBehavior::normalizeColumnType()
	 */
	public function normalizeColumnType(string $type): string {
		return $this->getBehavior('Search')->normalizeColumnType($type);
	}


	/**
	 * Check if search is active.
	 *
	 * @return bool
	 * @see \Awyiss\Model\Behavior\SearchBehavior::isActive()
	 */
	public function searchIsActive(): bool {
		return $this->getBehavior('Search')->isActive();
	}
}
