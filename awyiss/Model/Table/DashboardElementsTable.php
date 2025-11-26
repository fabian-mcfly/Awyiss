<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Core\App;
use Awyiss\Model\Entity\DashboardElement;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Enum\DateComparisonOperator;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use Collator;


/**
 * DashboardElements Model
 *
 * @method \Awyiss\Model\Entity\DashboardElement newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class DashboardElementsTable extends Table {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'dashboard_elements';


	/**
	 * An array of all available scopes
	 *
	 * @var array
	 */
	protected static array $scopes;


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'scope',
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('scope');
		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(function (DashboardElement $entity): bool {
			if (
				empty($entity->scope) ||
				empty($entity->settings) ||
				!is_array($entity->settings) ||
				empty($entity->settings['fields'])
			) {
				return true;
			}

			if (!is_array($entity->settings['fields'])) {
				return false;
			}

			$la_tableFields = $entity->scope ? $this->getTableFields($entity->scope) : [];
			unset($la_tableFields['page_role_id']);

			foreach ($entity->settings['fields'] as $ls_field) {
				if (!array_key_exists($ls_field, $la_tableFields)) {
					return false;
				}
			}

			return true;
		}, 'validListFields', [
			'errorField' => 'listFields',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_list_fields'),
		]);

		$rules->add(function (DashboardElement $entity): bool {
			if (
				empty($entity->scope) ||
				empty($entity->settings) ||
				!is_array($entity->settings) ||
				empty($entity->settings['filter'])
			) {
				return true;
			}

			if (!is_array($entity->settings['filter'])) {
				return false;
			}

			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = FactoryLocator::get('Table')->get(Inflector::camelize($entity->scope));
			$la_tableFilterColumns = $lo_table->getFilterColumns([], null, null, false);

			$la_operators = [];
			foreach (ComparisonOperator::cases() as $le_operator) {
				if ($le_operator === ComparisonOperator::Regexp) {
					continue;
				}

				$la_operators[] = $le_operator->value;
			}

			$la_dateOperators = [];
			foreach (DateComparisonOperator::cases() as $le_operator) {
				$la_dateOperators[] = $le_operator->value;
			}

			foreach ($entity->settings['filter'] as $ls_column => $la_columnSettings) {
				if (!array_key_exists($ls_column, $la_tableFilterColumns)) {
					return false;
				}

				if (empty($la_columnSettings['operator']) && $la_tableFilterColumns[ $ls_column ]->type === 'boolean') {
					return true;
				}

				if (in_array($la_tableFilterColumns[ $ls_column ]->type, ['date', 'datetime'], true)) {
					return !empty($la_columnSettings['operator']) && (
						in_array($la_columnSettings['operator'], $la_dateOperators, true) ||
						in_array($la_columnSettings['operator'], $la_operators, true)
					);
				}

				if (
					empty($la_columnSettings['operator']) ||
					!in_array($la_columnSettings['operator'], $la_operators, true) ||
					in_array($la_columnSettings['operator'], $la_tableFilterColumns[ $ls_column ]->disabledOperators, true)
				) {
					return false;
				}
			}

			return true;
		}, 'validFilterSettings', [
			'errorField' => 'filterSettings',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_filter_settings'),
		]);

		$rules->add(function (DashboardElement $entity): bool {
			if (
				empty($entity->scope) ||
				empty($entity->settings) ||
				!is_array($entity->settings) ||
				empty($entity->settings['sort'])
			) {
				return true;
			}

			if (!is_array($entity->settings['sort'])) {
				return false;
			}

			$la_tableFields = $entity->scope ? $this->getTableFields($entity->scope) : [];
			foreach ($entity->settings['sort'] as $la_sortSettings) {
				if (
					empty($la_sortSettings['field']) ||
					!array_key_exists($la_sortSettings['field'], $la_tableFields)
				) {
					return false;
				}

				if (
					empty($la_sortSettings['direction']) ||
					!in_array(strtolower($la_sortSettings['direction']), ['asc', 'desc'], true)
				) {
					return false;
				}
			}

			return true;
		}, 'validListSort', [
			'errorField' => 'listSort',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_list_sort'),
		]);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('access', 'json');
		$schema->setColumnType('settings', 'json');
	}


	/**
	 * @return array
	 */
	public function getAvailableScopes(): array {
		if (!empty(static::$scopes)) {
			return static::$scopes;
		}

		$la_classes = App::classes('*', 'Model/Table', 'Table');

		/**
		 * @var \Awyiss\Model\Table $ls_className
		 */
		foreach ($la_classes as $ls_tableName => $ls_className) {
			$ls_tableName = Inflector::underscore(substr($ls_tableName, 0, -5));

			if (
				str_starts_with($ls_tableName, 'attributes_') ||
				in_array($ls_tableName, [
					'audit',
					'content_template_content_areas',
					'content_template_elements',
					'dashboard_elements',
					'form_conditional_recipients',
					'generic_datatables',
					'i18n',
					'locks',
					'page_template_content_areas',
					'publication_data',
					'survey_survey_answers',
					'survey_survey_questions',
					'user_configuration',
					'usergroup_permissions',
					'usergroups_users',
					'widget_template_elements',
				])
			) {
				continue;
			}

			static::$scopes[ $ls_tableName ] = __d($ls_tableName, 'headline_overview');
		}

		/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
		$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$lo_pageRolesTable->findAllAndCache()->each(function (PageRole $pageRole): void {
			$ls_pageRole = Inflector::pluralize($pageRole->identifier);

			if (isset(static::$scopes[ $ls_pageRole ]) && !str_contains(static::$scopes[ $ls_pageRole ], '::')) {
				return;
			}

			static::$scopes[ $ls_pageRole ] = $pageRole->label;
		});


		/** @var \Awyiss\Model\Table\DatatablesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Datatables');
		$lo_table->findAllAndCache()->each(function (Datatable $datatable): void {
			if (isset(static::$scopes[ $datatable->identifier ]) && !str_contains(static::$scopes[ $datatable->identifier ], '::')) {
				return;
			}

			static::$scopes[ $datatable->identifier ] = $datatable->label;
		});

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

		uasort(static::$scopes, function (string $a, string $b) use ($lo_collator) {
			return $lo_collator->compare($a, $b);
		});

		return static::$scopes;
	}
}
