<?php declare(strict_types=1);


namespace Customer\Attribute\AttributeOptionsCollection;


use Awyiss\Attribute\AttributeOptionsCollection;


/**
 * This file will be ignored because the class is abstract
 */
abstract class AbstractAttributeOptionsCollection extends AttributeOptionsCollection {
	protected static string $scope = 'Abstract';


	/**
	 * Initializes the attribute options for the Contents scope.
	 *
	 * @return void
	 */
	public function initializeAttributeOptions(): void {
	}
}
