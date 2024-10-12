<?php declare(strict_types=1);


namespace Customer\Attribute\AttributeOptionsCollection;


use Awyiss\Attribute\AttributeOptionsCollection;


/**
 * Provides attribute options for the Widgets scope.
 */
class EmptyAttributeOptionsCollection extends AttributeOptionsCollection {
	protected static string $scope = 'Empty';


	/**
	 * Initializes the attribute options for the Contents scope.
	 *
	 * @return void
	 */
	public function initializeAttributeOptions(): void {
	}
}
