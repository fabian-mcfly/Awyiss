<?php declare(strict_types=1);


namespace Customer\Attribute\AttributeOptionsCollection;


use Awyiss\Attribute\AttributeOptionsCollection;


/**
 * This class will be ignored because of the underscore in its filename
 */
class IgnoredAttributeOptionsCollection extends AttributeOptionsCollection {
	protected static string $scope = 'Ignored';


	/**
	 * Initializes the attribute options for the Contents scope.
	 *
	 * @return void
	 */
	public function initializeAttributeOptions(): void {
	}
}
