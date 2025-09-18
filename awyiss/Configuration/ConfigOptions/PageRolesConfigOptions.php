<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the PageRoles scope
 */
class PageRolesConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption(
				defaultValue: true,
				identifier: 'autoCreateMenuEntries',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'identifier',
						'include_in_linklist',
					],
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
			'paginate' => [
				new ConfigOption(
					defaultValue: false,
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
		]);
	}
}
