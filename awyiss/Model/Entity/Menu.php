<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;


/**
 * Menu Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property string|null $languageShortcode
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property FrozenTime|null $createdOn
 * @property int|null $changedBy
 * @property FrozenTime|null $changedOn
 * @property int|null $deletedBy
 * @property FrozenTime|null $deletedOn
 */
class Menu extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
        'title' => true,
        'identifier' => true,
        'active' => true,
    ];
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
	 * @noinspection PhpUnused
	 */
	protected function _setIdentifier (string $as_identifier): string {
		$ls_identifier = Text::slug($as_identifier, ['replacement' => '_']);

		return mb_strtolower($ls_identifier);
	}
}
