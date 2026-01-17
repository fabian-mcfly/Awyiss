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
	 * Constructor
	 * Sets the `fieldTranslations` as a default field so CakePHP does not complain about
	 * additional settings.
	 */
	public function __construct() {
		$this->_defaultConfig['fieldTranslations'] = [];
	}


	/**
	 * Re-implementation of the original method to sanitize the data before passing it to the parent method.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function addSortingParams(array $data): void {
		if (isset($data['options']['sort']) && is_array($data['options']['sort'])) {
			$data['options']['sort'] = current($data['options']['sort']);
		}

		if (isset($data['options']['order'])) {
			// Make sure the fields of the _COALESCE key are flattened and at the
			$order = [];
			foreach ($data['options']['order'] as $field => $directionOrCoalesce) {
				if ($field === '_COALESCE') {
					foreach ($directionOrCoalesce['fields'] as $field) {
						if (!isset($order[ $field ])) {
							$order[ $field ] = $directionOrCoalesce['direction'];
						}
					}
				}
				elseif (!isset($order[ $field ])) {
					$order[ $field ] = $directionOrCoalesce;
				}
			}

			$data['options']['order'] = $order;
		}

		parent::addSortingParams($data);
	}


	/**
	 * Re-implementation of the original method to allow for sorting by multiple fields.
	 *
	 * @inheritDoc
	 */
	protected function getQuery(RepositoryInterface $object, ?QueryInterface $query, array $data): QueryInterface {
		$options = $data['options'];
		$queryOptions = array_intersect_key(
			$options,
			['order' => null, 'page' => null, 'limit' => null],
		);

		$args = [];
		$type = $options['finder'] ?? null;
		if (is_array($type)) {
			$args = (array)current($type);
			$type = key($type);
		}

		if ($query === null) {
			$query = $object->find($type ?? 'all', ...$args);
		}
		elseif ($type !== null) {
			$query->find($type, ...$args);
		}

		foreach ($queryOptions['order'] as $field => $directionOrCoalesce) {
			if ($field === '_COALESCE') {
				$fields = array_map(function ($field) {
					return new IdentifierExpression($field);
				}, $directionOrCoalesce['fields']);
				$expr = $query->func()->coalesce($fields);

				if ($directionOrCoalesce['direction'] === 'asc') {
					$query->orderByAsc($expr);
				}
				else {
					$query->orderByDesc($expr);
				}
			}
			elseif (isset($data['options']['fieldTranslations'][ $field ])) {
				$translations = $data['options']['fieldTranslations'][ $field ];
				$expr = $query->expr()->case();
				foreach ($translations as $translationKey => $translationValue) {
					$expr->when([$field => $translationKey])->then($translationValue);
				}
				$expr->else($field);
				if ($directionOrCoalesce === 'asc') {
					$query->orderByAsc($expr);
				}
				else {
					$query->orderByDesc($expr);
				}
			}
			else {
				$query->orderBy([$field => $directionOrCoalesce]);
			}
		}
		unset($queryOptions['order']);

		$query->applyOptions($queryOptions);

		return $query;
	}


	/**
	 * Re-implementation of the original method to allow for sorting by multiple fields.
	 *
	 * @param \Cake\Datasource\RepositoryInterface $object
	 * @param array $options
	 * @return array
	 */
	protected function validateSort(RepositoryInterface $object, array $options): array {
		if (isset($options['sort'])) {
			$direction = null;
			if (isset($options['direction'])) {
				$direction = strtolower($options['direction']);
			}
			if (!in_array($direction, ['asc', 'desc'], true)) {
				$direction = 'asc';
			}

			$defaultOrder = isset($options['order']) && is_array($options['order']) ? $options['order'] : [];
			if ($defaultOrder && is_string($options['sort']) && !str_contains($options['sort'], '.')) {
				$defaultOrder = $this->_removeAliases($defaultOrder, $object->getAlias());
			}

			if (is_array($options['sort'])) {
				$options['order'] = [
					'_COALESCE' => [
						'fields' => $options['sort'],
						'direction' => $direction,
					],
				];
			}
			else {
				$options['order'] = [$options['sort'] => $direction];
			}

			$options['order'] += $defaultOrder;
		}
		else {
			$options['sort'] = null;
		}
		unset($options['direction']);

		if (empty($options['order'])) {
			$options['order'] = [];
		}
		if (!is_array($options['order'])) {
			return $options;
		}
		$sortAllowed = false;
		if (isset($options['sortableFields'])) {
			$field = key($options['order']);

			if ($field === '_COALESCE') {
				$fields = $options['order'][ $field ]['fields'];
				foreach ($fields as $field) {
					$sortAllowed = in_array($field, $options['sortableFields'], true);
					if (!$sortAllowed) {
						$options['order'] = [];
						$options['sort'] = null;

						return $options;
					}
				}
			}
			else {
				$sortAllowed = in_array($field, $options['sortableFields'], true);
				if (!$sortAllowed) {
					$options['order'] = [];
					$options['sort'] = null;

					return $options;
				}
			}
		}

		if (
			$options['sort'] === null && count($options['order']) >= 1 && !is_numeric(key($options['order']))
		) {
			$options['sort'] = key($options['order']);
		}

		$options['order'] = $this->prefix($object, $options['order'], $sortAllowed);
		$options['fieldTranslations'] = $this->prefix($object, $options['fieldTranslations'], $sortAllowed);

		return $options;
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
		$cleanOrder = [];

		foreach ($order as $field => $directionOrCoalesce) {
			if ($field === '_COALESCE') {
				$fields = array_merge($this->_prefix($object, array_flip($directionOrCoalesce['fields']), $allowed));
				$directionOrCoalesce['fields'] = array_flip($fields);
			}

			$cleanOrder[ $field ] = $directionOrCoalesce;
		}

		return $this->_prefix($object, $cleanOrder, $allowed);
	}
}
