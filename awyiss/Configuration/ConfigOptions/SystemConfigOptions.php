<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;


class SystemConfigOptions extends AbstractConfigOptions {
	protected static string $scope = 'system';


	public function initializeConfigOptions (): void {
		$this->add([
			'frontend' => [
				new ConfigOption([
					'defaultValue' => TRUE,
					'localizable' => FALSE,
					'name' => 'editlinks',
					'nullable' => FALSE,
					'type' => ConfigOption::TYPE_BOOL,
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


		$this->add([
			'backend' => [
				new ConfigOption([
					'defaultValue' => 600,
					'localizable' => FALSE,
					'name' => 'lock_timeout',
					'nullable' => FALSE,
					'type' => ConfigOption::TYPE_INTEGER,
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