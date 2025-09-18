<?php declare(strict_types=1);


namespace Customer\Attribute\AttributeOptions;


use Awyiss\Attribute\AttributeOptionsCollection;
use Cake\Datasource\EntityInterface;


/**
 * Provides attribute options for the Contents scope.
 */
class ContentsAttributeOptions extends AttributeOptionsCollection {
	protected static string $scope = 'Contents';


	/**
	 * @inheritDoc
	 */
	public function initializeAttributeOptions(): void {
		$this->add([
			'backgroundColor' => [
				/*'disabled' => function(?EntityInterface $entity = null, array &$currentOptions) {
					$lo_date = new FrozenDate('now', $currentOptions['timezone'] ?? NULL);

					for ($i = 0; $i <= 5; $i++) {
						$lo_date = $lo_date->modify('+2 days');
						$la_options[] = $lo_date->format('Y-m-d');
					}

					return $la_options;
				},*/
				'options' => [
					'text' => 'Text',
					'dark' => 'Dunkel',
					'medium' => 'Mittel',
					'light' => 'Hell',
					'main' => 'Hauptfarbe',
					'contrast' => 'Kontrastfarbe',
				],
				/*'validate' => function() {
					return FALSE;
				},
				'value' => '',*/
			],
		]);

		/**
		 * Attribute options can also be added by passing an instance of `AttributeOptions` to the `add` method.
		 * This allows using named parameters for the constructor of `AttributeOptions`.
		 *
		 *	$this->add(new \Awyiss\Attribute\AttributeOptions(
		 *		identifier: 'backgroundColor',
		 *		options: [
		 *			'text' => 'Text',
		 *			'dark' => 'Dunkel',
		 *			'medium' => 'Mittel',
		 *			'light' => 'Hell',
		 *			'main' => 'Hauptfarbe',
		 *			'contrast' => 'Kontrastfarbe',
		 *		],
		 *	));
		 */
	}
}
