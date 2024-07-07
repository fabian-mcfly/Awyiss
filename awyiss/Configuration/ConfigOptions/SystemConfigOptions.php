<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the System scope
 */
class SystemConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'System';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_FRONTEND, [
			new ConfigOption(
				defaultValue: true,
				identifier: 'editor',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Bool,
			),
			'meta' => [
				new ConfigOption(
					defaultValue: 'Firma',
					identifier: 'titleAppendix',
				),
				new ConfigOption(
					defaultValue: ' | ',
					identifier: 'titleSeparator',
				),
			],
		]);


		$this->add(Awyiss::REALM_BACKEND, [
			'interface' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'darkMode',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 'plain',
					identifier: 'editor',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::ListKey,
					values: [
						'plain' => __d('system', 'interface_editor_plain'),
						'jodit' => __d('system', 'interface_editor_jodit'),
						'tinymce' => __d('system', 'interface_editor_tinymce'),
					],
				),
				new ConfigOption(
					defaultValue: 'regular',
					identifier: 'scale',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::ListKey,
					values: [
						'small' => __d('system', 'interface_scale_small'),
						'medium' => __d('system', 'interface_scale_medium'),
						'regular' => __d('system', 'interface_scale_regular'),
					],
				),
			],
			new ConfigOption(
				defaultValue: 600,
				identifier: 'lockTimeout',
				localizable: false,
				nullable: false,
				type: ConfigOptionType::Integer,
			),
			'meta' => [
				new ConfigOption(
					defaultValue: 'Awyiss Backend',
					identifier: 'titleAppendix',
				),
				new ConfigOption(
					defaultValue: ' | ',
					identifier: 'titleSeparator',
				),
			],
		]);
	}
}
