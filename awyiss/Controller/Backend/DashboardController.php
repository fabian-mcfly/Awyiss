<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\DashboardElement;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query\SelectQuery;


/**
 * Handles the dashboard of the backend
 */
class DashboardController extends Controller {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	protected ?string $defaultTable = '';


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return null;
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function overview(): void {
		$session = $this->request->getSession();
		$lastLogin = $session->read('Backend.lastLogin');

		$dashboardElements = [];
		foreach ($this->fetchTable('DashboardElements')->find('active')->all() as $dashboardElement) {
			$dashboardElements[ $dashboardElement->id ] = $this->buildDashboardElement($dashboardElement);
		}
		$dashboardElements = array_filter($dashboardElements);

		$this->set([
			'lastLogin' => $lastLogin,
			'dashboardElements' => $dashboardElements,
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\DashboardElement $element
	 * @return array|null
	 * @throws \Exception
	 */
	protected function buildDashboardElement(DashboardElement $element): ?array {
		if (!$this->elementIsAccessible($element)) {
			return null;
		}

		/** @var \Awyiss\Model\Table $table */
		$table = $this->fetchTable(Inflector::camelize($element->scope));
		$query = $table->find();

		$selectedOperators = $selectedValues = [];
		foreach ($element->settings['filter'] as $column => $columnSettings) {
			$selectedOperators[ $column ] = $columnSettings['operator'] ?? null;
			$selectedValues[ $column ] = $columnSettings['value'] ?? null;
		}

		$availableFields = $table->getFilterColumns([], $selectedOperators, $selectedValues, false);
		if ($availableFields && $selectedOperators && $selectedValues) {
			$table->searchFilterQuery($query, $availableFields);
		}

		$tableFields = $this->getTableFields($element->scope);
		if ($element->settings['sort']) {
			$this->applyQuerySorting($table, $query, $element->settings['sort'], $tableFields);
		}

		if ($element->settings['fields'] ?? []) {
			$this->containAssociations($table, $query, $element->settings['fields'] ?? []);
		}

		return [
			'records' => $query->limit($element->settings['limit'] ?? 20)->all(),
			'availableFields' => $tableFields,
			'selectedFields' => $element->settings['fields'] ?? [],
			'scope' => $element->scope,
			'title' => $element->title,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\DashboardElement $element
	 * @return bool
	 * @throws \Exception
	 */
	protected function elementIsAccessible(DashboardElement $element): bool {
		if (!$element->access) {
			return true;
		}

		if (!is_array($element->access['identifier'])) {
			$element->access['identifier'] = [$element->access['identifier']];
		}

		return $this->Authorization->scopeIsAccessible($element->access['scope'], [], ...$element->access['identifier']);
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array $selectedFields
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function containAssociations(Table $table, SelectQuery $query, array $selectedFields): SelectQuery {
		foreach ($table->associations() as $association) {
			if (!$association instanceof HasOne && !$association instanceof BelongsTo) {
				continue;
			}

			if (
				in_array($association->getProperty(), ['attributes', '_publication_start', '_publication_end']) ||
				str_starts_with($association->getProperty(), 'parent_')
			) {
				continue;
			}

			$key = $association->getForeignKey();
			if (is_array($key)) {
				$key = reset($key);
			}

			if (
				in_array($key, $selectedFields, true)
			) {
				$query->contain($association->getName());
			}
		}

		return $query;
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array $sort
	 * @param array $tableFields
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function applyQuerySorting(Table $table, SelectQuery $query, array $sort, array $tableFields): SelectQuery {
		foreach ($sort as $sortSetting) {
			$field = $sortSetting['field'];
			$direction = $sortSetting['direction'] === 'asc' ? 'asc' : 'desc';

			if (!array_key_exists($field, $tableFields)) {
				continue;
			}

			if (str_starts_with($field, 'attributes.')) {
				$field = $table->getAttributesTableName(true) . '.' . substr($field, 11);

				$query->orderBy([$field => $direction]);
				continue;
			}

			if (str_contains($field, '.')) {
				$query->orderBy([$field => $direction]);
				continue;
			}

			if (str_ends_with($field, '_id') && $field !== 'parent_id') {
				$this->applyQuerySortingByAssociation($table, $query, $field, $direction);
				continue;
			}

			if ($field === 'language_shortcode') {
				$this->applyQuerySortingByLanguage($query, $direction);
				continue;
			}

			$field = $table->aliasField($field);
			$query->orderBy([$field => $direction]);
		}

		return $query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $direction
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function applyQuerySortingByLanguage(SelectQuery $query, string $direction): SelectQuery {
		static $languages;

		if (!isset($languages)) {
			$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
		}

		Arrays::naturalSort($languages, 'title', false, $direction === 'desc' ? SORT_DESC : SORT_ASC);

		$query->orderBy(function (QueryExpression $exp) use ($languages) {
			$index = 0;

			$case = $exp->case();
			/** @var \Awyiss\Model\Entity\Language $language */
			foreach ($languages as $language) {
				$case->when(['language_shortcode' => $language->shortcode])->then($index, 'integer');

				$index++;
			}

			$case->else(999, 'integer');

			return $case;
		});

		return $query;
	}


	/**
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $field
	 * @param string $direction
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	protected function applyQuerySortingByAssociation(Table $table, SelectQuery $query, string $field, string $direction): SelectQuery {
		$associationName = substr($field, 0, -3);
		$associationName = Inflector::camelize(Inflector::pluralize($associationName));
		if (!$table->hasAssociation($associationName)) {
			return $query;
		}

		$association = $table->getAssociation($associationName);
		$displayField = $association->getDisplayField();
		$associationTable = $association->getTarget();

		if (
			!$associationTable->hasBehavior('Translate') ||
			!in_array(
				$displayField,
				$associationTable->getBehavior('Translate')->getConfig('fields')
			)
		) {
			$field = $associationTable->aliasField($displayField);
			$query->orderBy([$field => $direction]);
			return $query;
		}

		$expr = $query->func()->coalesce([
			new IdentifierExpression($associationName . '_' . $displayField . '_translation.content'),
			new IdentifierExpression($associationName . '.' . $displayField),
		]);

		if ($direction === 'asc') {
			$query->orderByAsc($expr);
		}
		else {
			$query->orderByDesc($expr);
		}

		return $query;
	}
}
