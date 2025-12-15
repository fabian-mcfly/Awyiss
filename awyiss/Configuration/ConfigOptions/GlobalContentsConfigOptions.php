<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Class GlobalContentsConfigOptions
 */
class GlobalContentsConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			new ConfigOption(
				defaultValue: [],
				identifier: 'knownIdentifiers',
				localizable: false,
				nullable: true,
				type: ConfigOptionType::List,
			),
			'overview' => [
				'columnView' => [
					new ConfigOption(
						defaultValue: true,
						identifier: 'enabled',
						localizable: false,
						nullable: false,
						personalizable: true,
						type: ConfigOptionType::Bool,
					),
				],
				new ConfigOption(
					defaultValue: [
						'global_content_template_id',
						'column_width',
						'column_indent',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$fields = $this->getTableFields();

						unset($fields['id']);

						return $fields;
					},
				),
			],
			'publicationData' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					nullable: false,
					localizable: false,
					type: ConfigOptionType::Bool,
				),
			],
		]);
	}
}
