<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\DashboardElement;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
use Collator;


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
		$lo_session = $this->request->getSession();
		$lo_lastLogin = $lo_session->read('Backend.lastLogin');

		$la_dashboardElements = [];
		$lo_dashboardElements = $this->fetchTable('DashboardElements')->find('active')->all();
		foreach ($lo_dashboardElements as $lo_element) {
			$la_dashboardElements[ $lo_element->id ] = $this->buildDashboardElement($lo_element);
		}
		$la_dashboardElements = array_filter($la_dashboardElements);

		$this->set([
			'lastLogin' => $lo_lastLogin,
			'dashboardElements' => $la_dashboardElements,
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

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->fetchTable(Inflector::camelize($element->scope));
		$lo_query = $lo_table->find();

		$la_selectedOperators = $la_selectedValues = [];
		foreach ($element->settings['filter'] as $ls_column => $la_columnSettings) {
			$la_selectedOperators[ $ls_column ] = $la_columnSettings['operator'] ?? null;
			$la_selectedValues[ $ls_column ] = $la_columnSettings['value'] ?? null;
		}

		$la_availableFields = $lo_table->getFilterColumns([], $la_selectedOperators, $la_selectedValues, false);
		if ($la_availableFields && $la_selectedOperators && $la_selectedValues) {
			$lo_table->searchFilterQuery($lo_query, $la_availableFields);
		}

		$la_tableFields = $this->getTableFields($element->scope);
		if ($element->settings['sort']) {
			$this->applyQuerySorting($lo_table, $lo_query, $element->settings['sort'], $la_tableFields);
		}

		if ($element->settings['fields'] ?? []) {
			$this->containAssociations($lo_table, $lo_query, $element->settings['fields'] ?? []);
		}

		return [
			'records' => $lo_query->limit($element->settings['limit'] ?? 20)->all(),
			'availableFields' => $la_tableFields,
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
		foreach ($table->associations() as $lo_association) {
			if (!$lo_association instanceof HasOne && !$lo_association instanceof BelongsTo) {
				continue;
			}

			if (
				in_array($lo_association->getProperty(), ['attributes', '_publication_start', '_publication_end']) ||
				str_starts_with($lo_association->getProperty(), 'parent_')
			) {
				continue;
			}

			$ls_key = $lo_association->getForeignKey();
			if (is_array($ls_key)) {
				$ls_key = reset($ls_key);
			}

			if (
				in_array($ls_key, $selectedFields, true)
			) {
				$query->contain($lo_association->getName());
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
		foreach ($sort as $la_sortSetting) {
			$ls_field = $la_sortSetting['field'];
			$ls_direction = $la_sortSetting['direction'] === 'asc' ? 'asc' : 'desc';

			if (!array_key_exists($ls_field, $tableFields)) {
				continue;
			}

			if (str_starts_with($ls_field, 'attributes.')) {
				$ls_field = $table->getAttributesTableName(true) . '.' . substr($ls_field, 11);

				$query->orderBy([$ls_field => $ls_direction]);
				continue;
			}

			if (str_contains($ls_field, '.')) {
				$query->orderBy([$ls_field => $ls_direction]);
				continue;
			}

			if (str_ends_with($ls_field, '_id') && $ls_field !== 'parent_id') {
				$this->applyQuerySortingByAssociation($table, $query, $ls_field, $ls_direction);
				continue;
			}

			if ($ls_field === 'language_shortcode') {
				$this->applyQuerySortingByLanguage($query, $ls_direction);
				continue;
			}

			$ls_field = $table->aliasField($ls_field);
			$query->orderBy([$ls_field => $ls_direction]);
		}

		return $query;
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param string $direction
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function applyQuerySortingByLanguage(SelectQuery $query, string $direction): SelectQuery {
		static $la_languages;

		if (!isset($la_languages)) {
			$la_languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
		}

		$lo_collator = new Collator(I18n::getLocale());
		/**
		 * Ignore case but not accents
		 * This will allow sorting 'Äpfel' after 'Apfel', not after 'Zitronen'
		 *
		 * @noinspection PhpExpectedValuesShouldBeUsedInspection, SpellCheckingInspection
		 */
		$lo_collator->setStrength(Collator::SECONDARY);
		// Enable natural sorting for numbers
		$lo_collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);

		uasort($la_languages, function (Language $a, Language $b) use ($lo_collator, $direction) {
			if ($direction === 'desc') {
				return $lo_collator->compare($b->title, $a->title);
			}

			return $lo_collator->compare($a->title, $b->title);
		});

		$query->orderBy(function (QueryExpression $exp) use ($la_languages) {
			$li_index = 0;

			$lo_case = $exp->case();
			/** @var \Awyiss\Model\Entity\Language $lo_language */
			foreach ($la_languages as $lo_language) {
				$lo_case->when(['language_shortcode' => $lo_language->shortcode])->then($li_index, 'integer');

				$li_index++;
			}

			$lo_case->else(999, 'integer');

			return $lo_case;
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
		$ls_associationName = substr($field, 0, -3);
		$ls_associationName = Inflector::camelize(Inflector::pluralize($ls_associationName));
		if (!$table->hasAssociation($ls_associationName)) {
			return $query;
		}

		$lo_association = $table->getAssociation($ls_associationName);
		$ls_displayField = $lo_association->getDisplayField();
		$lo_associationTable = $lo_association->getTarget();

		if (
			!$lo_associationTable->hasBehavior('Translate') ||
			!in_array(
				$ls_displayField,
				$lo_associationTable->getBehavior('Translate')->getConfig('fields')
			)
		) {
			$ls_field = $lo_associationTable->aliasField($ls_displayField);
			$query->orderBy([$ls_field => $direction]);
			return $query;
		}

		$la_fields = [
			new IdentifierExpression($ls_associationName . '_' . $ls_displayField . '_translation.content'),
			new IdentifierExpression($ls_associationName . '.' . $ls_displayField),
		];
		$lo_expr = $query->func()->coalesce($la_fields);

		if ($direction === 'asc') {
			$query->orderByAsc($lo_expr);
		}
		else {
			$query->orderByDesc($lo_expr);
		}

		return $query;
	}
}
