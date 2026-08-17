<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Core\App;
use Awyiss\Utility\Content\ColumnSystemInterface;


/**
 * Class ContentsConfigOptions
 */
class ContentsConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


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
					values: $this->getColumnSystemClasses(...),
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
						'contentTemplateId',
						'columnWidth',
						'columnIndent',
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


	/**
	 * @return array<<class-string<ColumnSystemInterface>, string>
	 */
	protected function getColumnSystemClasses(): array {
		$classes = [];

		/** @var class-string<ColumnSystemInterface> $class */
		$foundClasses = App::classes(
			'*',
			'Utility/Content',
			'ColumnSystem',
			ColumnSystemInterface::class,
			blocklistedClassNames: ['BackendColumnSystem']
		);
		foreach ($foundClasses as $class) {
			$classes[ $class ] = $class::getName();
		}

		return $classes;
	}
}
