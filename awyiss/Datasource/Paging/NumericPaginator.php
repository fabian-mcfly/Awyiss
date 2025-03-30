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
	 * @param array $data
	 * @return void
	 */
	protected function addSortingParams(array $data): void {
		$la_data = $data;

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
	 * @param \Cake\Datasource\RepositoryInterface $object
	 * @param \Cake\Datasource\QueryInterface|null $query
	 * @param array $data
	 * @return \Cake\Datasource\QueryInterface
	 */
	protected function getQuery(RepositoryInterface $object, ?QueryInterface $query, array $data): QueryInterface {
		$la_options = $data['options'];
		$la_queryOptions = array_intersect_key(
			$la_options,
			['order' => null, 'page' => null, 'limit' => null],
		);


		$la_args = [];
		$lx_type = $la_options['finder'] ?? null;
		if (is_array($lx_type)) {
			$la_args = (array)current($lx_type);
			$lx_type = key($lx_type);
		}

		$lo_query = $query;
		if ($lo_query === null) {
			$lo_query = $object->find($lx_type ?? 'all', ...$la_args);
		}
		elseif ($lx_type !== null) {
			$lo_query->find($lx_type, ...$la_args);
		}

		foreach ($la_queryOptions['order'] as $ls_field => $lx_directionOrCoalesce) {
			if ($ls_field === '_COALESCE') {
				$la_fields = array_map(function ($field) {
					return new IdentifierExpression($field);
				}, $lx_directionOrCoalesce['fields']);
				$lo_expr = $lo_query->func()->coalesce($la_fields);

				if ($lx_directionOrCoalesce['direction'] === 'asc') {
					$lo_query->orderByAsc($lo_expr);
				}
				else {
					$lo_query->orderByDesc($lo_expr);
				}
			}
			else {
				$lo_query->orderBy([$ls_field => $lx_directionOrCoalesce]);
			}
		}
		unset($la_queryOptions['order']);

		$lo_query->applyOptions($la_queryOptions);

		return $lo_query;
	}

	/**
	 * Re-implementation of the original method to allow for sorting by multiple fields.
	 *
	 * @param \Cake\Datasource\RepositoryInterface $object
	 * @param array $options
	 * @return array
	 */
	protected function validateSort(RepositoryInterface $object, array $options): array {
		$la_options = $options;

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
				$la_defaultOrder = $this->_removeAliases($la_defaultOrder, $object->getAlias());
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

		$la_options['order'] = $this->prefix($object, $la_options['order'], $lb_sortAllowed);

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
