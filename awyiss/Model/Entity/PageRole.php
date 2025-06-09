<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Utility\Text;


/**
 * PageRole Entity
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $identifier
 * @property bool $includeInLinklist
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class PageRole extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'identifier' => true,
		'includeInLinklist' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'include_in_linklist' => 'includeInLinklist',
		'system_order' => 'systemOrder',
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

		$ls_identifier = preg_replace('/\d/', '', $identifier);

		$ls_identifier = Text::slug($ls_identifier, ['replacement' => '_']);

		$ls_identifier = Inflector::singularize($ls_identifier);


		return strtolower($ls_identifier);
	}
}
