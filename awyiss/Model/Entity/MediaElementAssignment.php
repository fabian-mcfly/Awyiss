<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;


/**
 * MediaElementAssignment Entity
 *
 * @property int $id
 * @property int $mediaElementId
 * @property string $scope
 * @property int|null $foreignKey
 * @property \Awyiss\Model\Entity\MediaElement $mediaElement
 */
class MediaElementAssignment extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'mediaElementId' => true,
		'mediaElement' => true,
		'scope' => true,
		'foreignKey' => true,
	];


	/**
	 * Make sure the scope is always camelCased, free of special characters
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Configuration::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		$scope = Inflector::underscore($scope);
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);


		return Inflector::camelize($scope);
	}
}
