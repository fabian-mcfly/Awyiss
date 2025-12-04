<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Utility\Text;


/**
 * Datatable Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class Datatable extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'identifier' => true,
		'active' => true,
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\PageRole::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		$identifier = preg_replace('/\d/', '', $identifier);

		$identifier = Text::slug($identifier, ['replacement' => '_']);

		$identifier = Inflector::pluralize($identifier);


		return strtolower($identifier);
	}
}
