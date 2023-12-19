<?php declare(strict_types=1);


namespace FoobarCustomer\Attributes\AttributeOptionsCollection ;


use Awyiss\Attributes\AttributeOptionsCollection;
use Cake\I18n\FrozenDate;


class ContentsAttributeOptionsCollection extends AttributeOptionsCollection {
	protected static string $scope = 'Contents';


	public function initializeAttributeOptions (): void {
		$this->add([
			'background_color' => [
				'disabled' => function(&$aa_currentOptions) {
					$lo_date = new FrozenDate('now', $aa_currentOptions['timezone'] ?? NULL);

					for ($i = 0; $i <= 5; $i++) {
						$lo_date = $lo_date->modify('+2 days');
						$la_options[] = $lo_date->format('Y-m-d');
					}

					return $la_options;
				},
				'options' => function(&$aa_currentOptions) {
					$lo_date = new FrozenDate('now', $aa_currentOptions['timezone'] ?? NULL);

					$ls_prefix = '';
					if ($aa_currentOptions['language'] ?? NULL) {
						$ls_prefix = $aa_currentOptions['language']->title . ': ';
					}

					$la_options = [$lo_date->format('Y-m-d') => $lo_date->nice()];
					for ($i = 0; $i < 9; $i++) {

						$lo_date = $lo_date->modify('+1 day');
						$la_options[ $lo_date->format('Y-m-d') ] = $ls_prefix . $lo_date->nice();

					}

					return $la_options;
				},
				/*'validate' => function() {
					return FALSE;
				},
				'value' => [
					'2023-01-23',
					'2023-01-24'
				],*/
			],
			/*'testcheck' => [
				'value' => 555,
			]*/
		]);
	}
}
