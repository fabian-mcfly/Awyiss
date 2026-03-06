<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Class FormElementsConfigOptions
 */
class FormElementsConfigOptions extends AbstractConfigOptions {
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
						'identifier',
						'required',
						'columnWidth',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$fields = $this->getTableFields();

						unset($fields['id'], $fields['title']);

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
