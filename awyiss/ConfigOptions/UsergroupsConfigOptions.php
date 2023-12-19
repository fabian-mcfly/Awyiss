<?php declare(strict_types=1);


namespace Awyiss\ConfigOptions;


class UsergroupsConfigOptions extends AbstractConfigOptions {
	protected static ?string $scope = 'usergroups';


	public function initializeConfigOptions (): void {
		$this->add([
			'backend' => [
				new ConfigOption([
					'defaultValue' => TRUE,
					'name' => 'search',
					'type' => ConfigOption::TYPE_BOOL,
				]),
				'pagination' => [
					new ConfigOption([
						'defaultValue' => 99,
						'name' => 'limit',
						'type' => ConfigOption::TYPE_NUMBER,
					]),
				]
			]
		]);
	}
}