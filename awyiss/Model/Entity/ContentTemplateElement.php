<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int|null $contentTemplateId
 * @property string|null $identifier
 * @property string|null $title
 * @property string|null $fieldset
 * @property bool $required
 * @property \Awyiss\Model\Entity\ContentTemplate $contentTemplate
 */
class ContentTemplateElement extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'contentTemplateId' => true,
		'identifier' => true,
		'title' => true,
		'fieldset' => true,
		'required' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'content_template_id' => 'contentTemplateId',
	];


	/**
	 * In the database, the identifier exists as an underscored string
	 *
	 * @param string|null $as_identifier
	 * @return string|null
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$identifier
	 */
	public function _setIdentifier(?string $as_identifier): ?string {
		if ($as_identifier === null) {
			return null;
		}

		return Inflector::underscore($as_identifier);
	}
}
