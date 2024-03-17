<?php declare(strict_types=1);


namespace Customer\Attributes\AttributeOptionsCollection;


use Awyiss\Attributes\AttributeOptionsCollection;
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
	public function initializeAttributeOptions(): void {
		$this->add([
			'backgroundColor' => [
				'options' => function (/*EntityInterface $ao_entity, array &$aa_currentOptions*/): array {
					//Return a list of dummy colors (css class names) and their labels
					return [
						'primary' => 'Primary',
						'secondary' => 'Secondry',
						'success' => 'Success',
						'danger' => 'Danger',
						'warning' => 'Warning',
						'info' => 'Info',
						'light' => 'Light',
						'dark' => 'Dark',
					];
				},
			],
		]);
	}
}
