<?php declare(strict_types=1);


namespace Customer\Attribute\AttributeOptions;


use Awyiss\Attribute\AttributeOptionsCollection;
use Cake\Datasource\EntityInterface;


/**
 * Provides attribute options for the GlobalContents scope.
 */
class GlobalContentsAttributeOptions extends AttributeOptionsCollection {
	/**
	 * Initializes the attribute options for the Contents scope.
	 *
	 * @return void
	 */
	public function initializeAttributeOptions(): void {
		$this->add([
			'backgroundColor' => [
				/**
				 * Disables the options 'dark' and 'light' for the background color attribute.
				 */
				'disabled' => function (EntityInterface $entity, array &$currentOptions) {
					return ['dark', 'light'];
				},
				/**
				 * Provides the options for the background color attribute.
				 */
				'options' => function (?EntityInterface $entity, &$currentOptions) {
					return [
						'text' => 'Text',
						'dark' => 'Dunkel',
						'medium' => 'Mittel',
						'light' => 'Hell',
						'main' => 'Hauptfarbe',
						'contrast' => 'Kontrastfarbe',
					];
				},
				/**
				 * Allows only the values 'main' and null for the background color attribute.
				 */
				'validate' => function (mixed $value) {
					return $value === null || $value === 'main';
				},
			],
		]);
	}
}
