<?php declare(strict_types=1);


namespace Awyiss\ConfigOptions;


class SystemConfigOptions extends AbstractConfigOptions {
	protected static ?string $scope = 'system';


	public function initializeConfigOptions (): void {
		$this->add([
			'frontend' => [
				new ConfigOption([
					'defaultValue' => TRUE,
					'name' => 'editlinks',
					'type' => ConfigOption::TYPE_BOOL,
				]),
				'media' => [
					new ConfigOption([
						'defaultValue' => [2560, 1920, 1680, 1280, 1024, 768, 640, 480, 360],
						'name' => 'default_breakpoints',
						'type' => ConfigOption::TYPE_JSONLIST,
					]),
				],
				'meta' => [
					new ConfigOption([
						'defaultValue' => 'Firma',
						'name' => 'title_appendix',
					]),
					new ConfigOption([
						'defaultValue' => ' | ',
						'name' => 'title_separator',
					]),
				]
			]
		]);


		$this->add([
			'backend' => [
				new ConfigOption([
					'defaultValue' => 600,
					'name' => 'lock_timeout',
					'type' => ConfigOption::TYPE_NUMBER,
				]),
				'meta' => [
					new ConfigOption([
						'defaultValue' => 'Firma',
						'name' => 'title_appendix',
					]),
					new ConfigOption([
						'defaultValue' => ' | ',
						'name' => 'title_separator',
					]),
				]
			]
		]);
	}
}