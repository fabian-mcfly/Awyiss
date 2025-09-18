<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Class WidgetsConfigOptions
 */
class WidgetsConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
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
						'widget_template_id',
						'column_width',
						'column_indent',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$la_fields = $this->getTableFields();

						unset($la_fields['id']);

						return $la_fields;
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
