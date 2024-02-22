<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Inflector;
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
	protected array $_accessible = [
		'title' => true,
		'identifier' => true,
		'active' => true,
	];
	/**
	 * Entity to be passed to the validation of attributes
	 */
	protected ?Entity $entity = null;
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $as_identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\PageRole::$identifier
	 */
	protected function _setIdentifier(?string $as_identifier): ?string {
		if ($as_identifier === null) {
			return null;
		}

		$ls_identifier = preg_replace('/\d/', '', $as_identifier);

		$ls_identifier = Text::slug($ls_identifier, ['replacement' => '_']);

		$ls_identifier = Inflector::pluralize($ls_identifier);


		return strtolower($ls_identifier);
	}
}
