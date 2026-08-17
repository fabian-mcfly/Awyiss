<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Migrations\BaseMigration;


/**
 * This migration renames all scopes and identifiers in the database to use camelCase instead of underscores.
 */
class RenameScopesAndIdentifiers extends BaseMigration {
	/**
	 * @var array[]
	 */
	protected static $tableFields = [
		'Attributes' => [
			'scope' => 'camelize',
			'identifier' => 'variable',
			'inputType' => 'variable',
		],
		'Audit' => [
			'scope' => 'camelize',
			'subjectLeftTable' => 'camelize',
			'subjectRightTable' => 'camelize',
		],
		'BackendMenuEntries' => [
			'insertAfterId' => 'variable',
		],
		'Configuration' => [
			'scope' => 'camelize',
			'identifier' => 'variable',
		],
		'ContentTemplateElements' => [
			'identifier' => 'variable',
		],
		'CustomerGroupAccessSettings' => [
			'scope' => 'camelize',
			'accessType' => 'variable',
		],
		'CustomerGroupAssignments' => [
			'scope' => 'camelize',
		],
		'DashboardElements' => [
			'scope' => 'camelize',
		],
		'Datatables' => [
			'identifier' => 'camelize',
		],
		'FormConditionalRecipients' => [
			'type' => 'variable',
			'field' => 'variable',
			'operator' => 'variable',
		],
		'FormElements' => [
			'type' => 'variable',
			'identifier' => 'variable',
		],
		'Forms' => [
			'identifier' => 'variable',
			'conditionalRecipientsStrategy' => 'variable',
		],
		'GlobalContentTemplateElements' => [
			'identifier' => 'variable',
		],
		'I18N' => [
			'model' => 'camelize',
			'field' => 'variable',
		],
		'Locks' => [
			'scope' => 'camelize',
		],
		'MediaAssignments' => [
			'scope' => 'camelize',
			'mediaElementSelectorIdentifier' => 'variable',
		],
		'MediaElementAssignments' => [
			'scope' => 'camelize',
		],
		'MediaElementSelectors' => [
			'identifier' => 'variable',
		],
		'MediaElements' => [
			'identifier' => 'variable',
		],
		'MediaSelectors' => [
			'identifier' => 'variable',
		],
		'Menus' => [
			'identifier' => 'variable',
		],
		'PublicationData' => [
			'scope' => 'camelize',
		],
		'SurveyEntries' => [
			'identifier' => 'variable',
		],
		'SurveyQuestions' => [
			'type' => 'variable',
		],
		'SurveySurveyAnswers' => [
			'nextAction' => 'variable',
		],
		'SurveySurveyQuestions' => [
			'nextAction' => 'variable',
		],
		'Surveys' => [
			'identifier' => 'variable',
			'finalAction' => 'variable',
		],
		'UrlHistory' => [
			'scope' => 'camelize',
		],
		'UserConfiguration' => [
			'scope' => 'camelize',
			'identifier' => 'variable',
		],
		'UsergroupPermissions' => [
			'scope' => 'camelize',
			'identifier' => 'variable',
		],
	];


	/**
	 * Traverse all specified tables and rename the values of each table
	 * to the format specified in the $tableFields property.
	 *
	 * @return void
	 */
	public function up(): void {
		$tableLocator = FactoryLocator::get('Table');
		foreach (self::$tableFields as $tableName => $fields) {
			/** @var \Awyiss\Model\Table $table */
			$table = $tableLocator->get($tableName);
			$entities = $table
				->find('all', softDelete: ['includeDeleted' => true])
				->disableResultsCasting()
				->toArray()
			;

			/** @var \Cake\Datasource\EntityInterface $entity */
			foreach ($entities as &$entity) {
				$updatedData = false;
				foreach ($fields as $field => $format) {
					if (!isset($entity[ $field ]) || !is_string($entity[ $field ])) {
						continue;
					}

					$value = $entity->get($field);
					$newValue = Inflector::$format($value);

					if ($value !== $newValue) {
						$entity->set($field, $newValue);
						$updatedData = true;
					}
				}

				if ($updatedData === false) {
					$entity = null;
				}
			}
			unset($entity);

			/** @var array<\Cake\Datasource\EntityInterface> $entities */
			$entities = array_filter($entities);

			if (!empty($entities)) {
				$table->updateAll(function (QueryExpression $expression) use ($entities, $fields) {
					$cases = [];
					foreach ($fields as $field => $format) {
						$fieldCase = $expression->case();

						foreach ($entities as $entity) {
							$fieldCase
								->when(['id = ' . $entity->id])
								->then($entity->get($field), 'string')
							;
						}

						$cases[ $field ] = $fieldCase;
					}

					return $cases;
				}, [
					'id IN' => array_map(static fn($entity) => $entity->id, $entities),
				]);
			}
		}

		/** @var \Awyiss\Model\Table\ConfigurationTable $configurationTable */
		$configurationTable = $tableLocator->get('Configuration');

		// In Configuration, set `protection.methods` to camelCased values for the Forms scope
		$record = $configurationTable
			->find()
			->where(['scope' => 'Forms', 'identifier' => 'protection.methods'])
			->first()
		;
		if ($record) {
			$methods = json_decode($record->value, true);
			if (is_array($methods)) {
				$updatedMethods = array_map(static function ($method) {
					return Inflector::variable($method);
				}, $methods);

				if ($methods !== $updatedMethods) {
					$configurationTable->updateAll(['value' => json_encode($updatedMethods)], ['id' => $record->id]);
				}
			}
		}

		/**
		 * In Configuration, set `systemOrder.field` to camelCased values
		 */
		$records = $configurationTable
			->find()
			->where(['identifier' => 'systemOrder.field'])
		;
		if ($records->count()) {
			foreach ($records as $record) {
				$newValue = Inflector::variable($record->value);

				if ($record->value !== $newValue) {
					$configurationTable->updateAll(['value' => $newValue], ['id' => $record->id]);
				}
			}
		}

		// In Configuration, set `overview.displayedFields` to camelCased values
		/** @noinspection DuplicatedCode */
		$records = $configurationTable
			->find()
			->where(['identifier' => 'overview.displayedFields'])
		;
		if ($records->count()) {
			foreach ($records as $record) {
				if (empty($record->value)) {
					continue;
				}
				$fields = json_decode($record->value, true);
				if (is_array($fields)) {
					$updatedFields = array_map(static function ($field) {
						return Inflector::variable($field);
					}, $fields);

					if ($fields !== $updatedFields) {
						$configurationTable->updateAll(['value' => json_encode($updatedFields)], ['id' => $record->id]);
					}
				}
			}
		}

		// In UserConfiguration, set `overview.displayedFields` to camelCased values
		$userConfigurationTable = $tableLocator->get('UserConfiguration');
		/** @noinspection DuplicatedCode */
		$records = $userConfigurationTable
			->find()
			->where(['identifier' => 'overview.displayedFields'])
		;
		if ($records->count()) {
			foreach ($records as $record) {
				if (empty($record->value)) {
					continue;
				}
				$fields = json_decode($record->value, true);
				if (is_array($fields)) {
					$updatedFields = array_map(static function ($field) {
						return Inflector::variable($field);
					}, $fields);

					if ($fields !== $updatedFields) {
						$userConfigurationTable->updateAll(['value' => json_encode($updatedFields)], ['id' => $record->id]);
					}
				}
			}
		}

		// In DashboardElements, simply preg_replace all underscores in the `settings` JSON value to camelCase
		$dashboardElementsTable = $tableLocator->get('DashboardElements');
		$records = $dashboardElementsTable
			->find()
			->where(['settings IS NOT' => null])
		;
		if ($records->count()) {
			foreach ($records as $record) {
				$record->settings = json_encode($record->settings);
				$settings = preg_replace_callback(
					'/(_([a-z]))/',
					static function ($matches) {
						return strtoupper($matches[2]);
					},
					$record->settings
				);

				if ($settings !== $record->settings) {
					$dashboardElementsTable->updateAll(['settings' => json_decode($settings), true], ['id' => $record->id]);
				}
			}
		}
	}
}