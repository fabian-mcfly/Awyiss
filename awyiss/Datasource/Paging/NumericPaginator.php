<?php declare(strict_types=1);


namespace Awyiss\Datasource\Paging;


use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\Paging\NumericPaginator as BaseNumericPaginator;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;


/**
 * Class NumericPaginator
 *	- Allows for sorting by multiple fields.
 */
class NumericPaginator extends BaseNumericPaginator {
	/**
	 * Re-implementation of the original method to sanitize the data before passing it to the parent method.
	 *
	 * @param array $aa_data
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection*/
	protected function addSortingParams(array $aa_data): void {
		$la_data = $aa_data;

		if (isset($la_data['options']['sort']) && is_array($la_data['options']['sort'])) {
			$la_data['options']['sort'] = current($la_data['options']['sort']);
		}

		if (isset($la_data['options']['order'])) {
			// Make sure the fields of the _COALESCE key are flattened and at the
			$la_order = [];
			foreach ($la_data['options']['order'] as $ls_field => $lx_directionOrCoalesce) {
				if ($ls_field === '_COALESCE') {
					foreach ($lx_directionOrCoalesce['fields'] as $ls_field) {
						if (!isset($la_order[ $ls_field ])) {
							$la_order[ $ls_field ] = $lx_directionOrCoalesce['direction'];
						}
					}
				}
				elseif (!isset($la_order[ $ls_field ])) {
					$la_order[ $ls_field ] = $lx_directionOrCoalesce;
				}
			}

			$la_data['options']['order'] = $la_order;
		}

		parent::addSortingParams($la_data);
	}

	/**
	 * @param \Cake\Datasource\RepositoryInterface $ao_object
	 * @param \Cake\Datasource\QueryInterface|null $ao_query
	 * @param array $aa_data
	 * @return \Cake\Datasource\QueryInterface
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function getQuery(RepositoryInterface $ao_object, ?QueryInterface $ao_query, array $aa_data): QueryInterface {
		$la_options = $aa_data['options'];
		$la_queryOptions = array_intersect_key(
			$la_options,
			['order' => null, 'page' => null, 'limit' => null],
		);

		if ($ao_query === null) {
			$la_args = [];
			$lx_finder = !empty($la_options['finder']) ? $la_options['finder'] : 'all';
			if (is_array($lx_finder)) {
				$la_args = (array)current($lx_finder);
				$lx_finder = key($lx_finder);
			}

			$ao_query = $ao_object->find($lx_finder, ...$la_args);
		}

		foreach ($la_queryOptions['order'] as $ls_field => $lx_directionOrCoalesce) {
			if ($ls_field === '_COALESCE') {
				$la_fields = array_map(function ($field) {
					return new IdentifierExpression($field);
				}, $lx_directionOrCoalesce['fields']);
				$lo_expr = $ao_query->func()->coalesce($la_fields);

				if ($lx_directionOrCoalesce['direction'] === 'asc') {
					$ao_query->orderByAsc($lo_expr);
				}
				else {
					$ao_query->orderByDesc($lo_expr);
				}
			}
			else {
				$ao_query->orderBy([$ls_field => $lx_directionOrCoalesce]);
			}
		}
		unset($la_queryOptions['order']);

		$ao_query->applyOptions($la_queryOptions);

		return $ao_query;
	}

	/**
	 * Re-implementation of the original method to allow for sorting by multiple fields.
	 *
	 * @param \Cake\Datasource\RepositoryInterface $ao_object
	 * @param array $aa_options
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function validateSort(RepositoryInterface $ao_object, array $aa_options): array {
		$la_options = $aa_options;

		if (isset($la_options['sort'])) {
			$ls_direction = null;
			if (isset($la_options['direction'])) {
				$ls_direction = strtolower($la_options['direction']);
			}
			if (!in_array($ls_direction, ['asc', 'desc'], true)) {
				$ls_direction = 'asc';
			}

			$la_defaultOrder = isset($la_options['order']) && is_array($la_options['order']) ? $la_options['order'] : [];
			if ($la_defaultOrder && is_string($la_options['sort']) && !str_contains($la_options['sort'], '.')) {
				$la_defaultOrder = $this->_removeAliases($la_defaultOrder, $ao_object->getAlias());
			}

			if (is_array($la_options['sort'])) {
				$la_options['order'] = [
					'_COALESCE' => [
						'fields' => $la_options['sort'],
						'direction' => $ls_direction,
					],
				];
			}
			else {
				$la_options['order'] = [$la_options['sort'] => $ls_direction];
			}

			$la_options['order'] += $la_defaultOrder;
		}
		else {
			$la_options['sort'] = null;
		}
		unset($la_options['direction']);

		if (empty($la_options['order'])) {
			$la_options['order'] = [];
		}
		if (!is_array($la_options['order'])) {
			return $la_options;
		}

		$lb_sortAllowed = false;
		if (isset($la_options['sortableFields'])) {
			$ls_field = key($la_options['order']);

			if ($ls_field === '_COALESCE') {
				$la_fields = $la_options['order'][ $ls_field ]['fields'];
				foreach ($la_fields as $ls_field) {
					$lb_sortAllowed = in_array($ls_field, $la_options['sortableFields'], true);
					if (!$lb_sortAllowed) {
						$la_options['order'] = [];
						$la_options['sort'] = null;

						return $la_options;
					}
				}
			}
			else {
				$lb_sortAllowed = in_array($ls_field, $la_options['sortableFields'], true);
				if (!$lb_sortAllowed) {
					$la_options['order'] = [];
					$la_options['sort'] = null;

					return $la_options;
				}
			}
		}

		if (
			$la_options['sort'] === null && count($la_options['order']) >= 1 && !is_numeric(key($la_options['order']))
		) {
			$la_options['sort'] = key($la_options['order']);
		}

		$la_options['order'] = $this->prefix($ao_object, $la_options['order'], $lb_sortAllowed);

		return $la_options;
	}


	/**
	 * Prefix the fields with the table alias if they are not already prefixed.
	 * Proxies to the original method for regular fields, as well as for fields inside the _COALESCE key.
	 *
	 * @param \Cake\Datasource\RepositoryInterface $object
	 * @param array $order
	 * @param bool $allowed
	 * @return array
	 */
	protected function prefix(RepositoryInterface $object, array $order, bool $allowed = false): array {
		$la_order = [];

		foreach ($order as $ls_field => $lx_directionOrCoalesce) {
			if ($ls_field === '_COALESCE') {
				$la_fields = array_merge($this->_prefix($object, array_flip($lx_directionOrCoalesce['fields']), $allowed));
				$lx_directionOrCoalesce['fields'] = array_flip($la_fields);
			}

			$la_order[ $ls_field ] = $lx_directionOrCoalesce;
		}

		return $this->_prefix($object, $la_order, $allowed);
	}
}
