<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Class ContentsConfigOptions
 */
class ContentsConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'Contents';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'columnSystem' => [
				new ConfigOption(
					defaultValue: '\Awyiss\Utility\Content\AwyissColumnSystem',
					identifier: 'className',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getColumnSystemClasses()
				),
				new ConfigOption(
					defaultValue: 5,
					identifier: 'maxColumns',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Integer,
				),
			],
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
						'content_template_id',
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


	/**
	 * @return array
	 */
	protected function getColumnSystemClasses(): array {
		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Utility\Content\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Utility', 'Content', '*ColumnSystem.php',]),
			'\Awyiss\Utility\Content\\' => implode(DS, [ROOT, APP_DIR, 'Utility', 'Content', '*ColumnSystem.php']),
		];

		$la_columnSystemClasses = [];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_configurationName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				/**
				 * @var class-string<\Awyiss\Utility\Content\ColumnSystemInterface> $ls_configurationClass
				 */
				$ls_configurationClass = $ls_namespace . $ls_configurationName;

				if (!is_callable([$ls_configurationClass, 'getName']) || isset($la_columnSystemClasses[ $ls_configurationClass ])) {
					continue;
				}

				$la_columnSystemClasses[ $ls_configurationClass ] = $ls_configurationClass::getName();
			}
		}


		return $la_columnSystemClasses;
	}
}
