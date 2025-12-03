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
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


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

			$tableFields = $entity->scope ? $this->getTableFields($entity->scope) : [];
			unset($tableFields['page_role_id']);

			return array_all($entity->settings['fields'], fn ($field) => array_key_exists($field, $tableFields));
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

			/** @var \Awyiss\Model\Table $table */
			$table = FactoryLocator::get('Table')->get(Inflector::camelize($entity->scope));
			$tableFilterColumns = $table->getFilterColumns([], null, null, false);

			$operators = [];
			foreach (ComparisonOperator::cases() as $operator) {
				if ($operator === ComparisonOperator::Regexp) {
					continue;
				}

				$operators[] = $operator->value;
			}

			$dateOperators = [];
			foreach (DateComparisonOperator::cases() as $operator) {
				$dateOperators[] = $operator->value;
			}

			foreach ($entity->settings['filter'] as $column => $columnSettings) {
				if (!array_key_exists($column, $tableFilterColumns)) {
					return false;
				}

				if (empty($columnSettings['operator']) && $tableFilterColumns[ $column ]->type === 'boolean') {
					return true;
				}

				if (in_array($tableFilterColumns[ $column ]->type, ['date', 'datetime'], true)) {
					return !empty($columnSettings['operator']) && (
						in_array($columnSettings['operator'], $dateOperators, true) ||
						in_array($columnSettings['operator'], $operators, true)
					);
				}

				if (
					empty($columnSettings['operator']) ||
					!in_array($columnSettings['operator'], $operators, true) ||
					in_array($columnSettings['operator'], $tableFilterColumns[ $column ]->disabledOperators, true)
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

			$tableFields = $entity->scope ? $this->getTableFields($entity->scope) : [];
			foreach ($entity->settings['sort'] as $sortSettings) {
				if (
					empty($sortSettings['field']) ||
					!array_key_exists($sortSettings['field'], $tableFields)
				) {
					return false;
				}

				if (
					empty($sortSettings['direction']) ||
					!in_array(strtolower($sortSettings['direction']), ['asc', 'desc'], true)
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

		$classes = App::classes('*', 'Model/Table', 'Table');

		/**
		 * @var \Awyiss\Model\Table $className
		 */
		foreach ($classes as $tableName => $className) {
			$tableName = Inflector::underscore(substr($tableName, 0, -5));

			if (
				str_starts_with($tableName, 'attributes_') ||
				in_array($tableName, [
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

			static::$scopes[ $tableName ] = __d($tableName, 'headline_overview');
		}

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$pageRolesTable->findAllAndCache()->each(function (PageRole $pageRole): void {
			$pageRoleName = Inflector::pluralize($pageRole->identifier);

			if (isset(static::$scopes[ $pageRoleName ]) && !str_contains(static::$scopes[ $pageRoleName ], '::')) {
				return;
			}

			static::$scopes[ $pageRoleName ] = $pageRole->label;
		});


		/** @var \Awyiss\Model\Table\DatatablesTable $table */
		$table = FactoryLocator::get('Table')->get('Datatables');
		$table->findAllAndCache()->each(function (Datatable $datatable): void {
			if (isset(static::$scopes[ $datatable->identifier ]) && !str_contains(static::$scopes[ $datatable->identifier ], '::')) {
				return;
			}

			static::$scopes[ $datatable->identifier ] = $datatable->label;
		});

		Arrays::naturalSort(static::$scopes);

		return static::$scopes;
	}
}
