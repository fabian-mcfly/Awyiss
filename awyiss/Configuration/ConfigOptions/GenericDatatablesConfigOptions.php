<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractGenericConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableNamesTrait;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;


/**
 * Provides all configuration options for the generic datatables scope
 */
class GenericDatatablesConfigOptions extends AbstractGenericConfigOptions {
	use TableNamesTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'categories' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'allowAggregation',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'associationName',
					localizable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getTableNames(...),
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'categories',
					localizable: false,
					type: ConfigOptionType::Json,
				),
				new ConfigOption(
					defaultValue: 'category',
					identifier: 'identifier',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::String
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'threaded',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'useDatasource',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'mediaFolders' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'autoCreate',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool
				),
			],
			'overview' => [
				new ConfigOption(
					defaultValue: [],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$la_fields = $this->getTableFields();

						unset($la_fields['id'], $la_fields['title']);

						return $la_fields;
					},
				),
			],
			'nest' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'paginate' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 20,
					identifier: 'limit',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Integer,
				),
			],
			'systemOrder' => [
				new ConfigOption(
					defaultValue: SORT_ASC,
					identifier: 'direction',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					typecast: function (mixed $value): ?int {
						if ($value === 'asc' || intval($value) === SORT_ASC) {
							return SORT_ASC;
						}

						if ($value === 'desc' || intval($value) === SORT_DESC) {
							return SORT_DESC;
						}

						return null;
					},
					values: [
						SORT_ASC => __d(Inflector::underscore($this->getDynamicScope()), 'sort_asc'),
						SORT_DESC => __d(Inflector::underscore($this->getDynamicScope()), 'sort_desc'),
					],
				),
				new ConfigOption(
					defaultValue: 'title',
					identifier: 'field',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getTableFields(...),
				),
			],
		]);

		$lo_splitIntoLanguages = new ConfigOption(
			defaultValue: true,
			identifier: 'splitIntoLanguages',
			localizable: false,
			nullable: false,
			type: ConfigOptionType::Bool,
		);
		$lo_splitIntoLanguages->setValidate(function (mixed $value, ?string $languageShortcode = null) use ($lo_splitIntoLanguages): bool|string {
			if ($value) {
				$ls_scope = $this->getDynamicScope();
				if (Configure::read('Awyiss.' . $ls_scope . '.Backend.translatable')) {
					return __d('configuration', 'error_option_when_split_into_languages_when_translatable');
				}
			}

			return $lo_splitIntoLanguages->validate($value, $languageShortcode);
		});

		$this->add('Backend', $lo_splitIntoLanguages);

		$lo_translatable = new ConfigOption(
			defaultValue: false,
			identifier: 'translatable',
			localizable: false,
			nullable: false,
			type: ConfigOptionType::Bool,
		);
		$lo_translatable->setValidate(function (mixed $value, ?string $languageShortcode = null) use ($lo_translatable): bool|string {
			if ($value) {
				$ls_scope = $this->getDynamicScope();
				if (Configure::read('Awyiss.' . $ls_scope . '.Backend.splitIntoLanguages')) {
					return __d('configuration', 'error_option_not_translatable_when_split_into_languages');
				}
			}

			return $lo_translatable->validate($value, $languageShortcode);
		});

		$this->add('Backend', $lo_translatable);
	}
}
