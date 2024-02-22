<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractGenericConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the generic datatables scope
 */
class GenericDatatablesConfigOptions extends AbstractGenericConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'GenericDatatables';


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
					nullable: true,
					type: ConfigOptionType::String,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'categories',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::JsonArray,
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
					type: ConfigOptionType::Bool
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
			'nest' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::Bool,
				),
			],
			'paginate' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 20,
					identifier: 'limit',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Integer,
				),
			],
			new ConfigOption(
				defaultValue: true,
				identifier: 'splitIntoLanguages',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
			'systemOrder' => [
				new ConfigOption(
					defaultValue: SORT_ASC,
					identifier: 'direction',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListValue,
					values: [
						SORT_ASC,
						SORT_DESC,
					],
				),
				new ConfigOption(
					defaultValue: 'title',
					identifier: 'field',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListValue,
					values: $this->getSystemOrderFields(...),
				),
			],
			new ConfigOption(
				defaultValue: false,
				identifier: 'translatable',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
		]);
	}
}
