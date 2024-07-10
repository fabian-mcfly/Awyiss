<?php declare(strict_types=1);


namespace Customer\Attribute\AttributeOptionsCollection;


use Awyiss\Attribute\AttributeOptionsCollection;
use Cake\Datasource\EntityInterface;


/**
 * Provides attribute options for the Contents scope.
 */
class ContentsAttributeOptionsCollection extends AttributeOptionsCollection {
	protected static string $scope = 'Contents';


	/**
	 * Initializes the attribute options for the Contents scope.
	 *
	 * @return void
	 */
	public function initializeAttributeOptions (): void {
		$this->add([
			'backgroundColor' => [
				/*'disabled' => function(EntityInterface $entity, array &$currentOptions) {
					$lo_date = new FrozenDate('now', $currentOptions['timezone'] ?? NULL);

					for ($i = 0; $i <= 5; $i++) {
						$lo_date = $lo_date->modify('+2 days');
						$la_options[] = $lo_date->format('Y-m-d');
					}

					return $la_options;
				},*/
				'options' => function(EntityInterface $entity, &$currentOptions) {
					$la_options = [
						'text' => 'Text',
						'dark' => 'Dunkel',
						'medium' => 'Mittel',
						'light' => 'Hell',
						'main' => 'Hauptfarbe',
						'contrast' => 'Kontrastfarbe',
					];

					return $la_options;
				},
				/*'validate' => function() {
					return FALSE;
				},
				'value' => '',*/
			],
		]);
	}
}
