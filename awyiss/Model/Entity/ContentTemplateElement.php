<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Inflector;


/**
 * UsergroupPermission Entity
 *
 * @property int $id
 * @property int $contentTemplateId
 * @property string $identifier
 * @property string $title
 * @property string $fieldset
 * @property bool $required
 * @property ContentTemplate $contentTemplate
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
	 * @param string $as_identifier
	 * @return string
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$identifier
	 */
	public function _setIdentifier(string $as_identifier): string {
		return Inflector::underscore($as_identifier);
	}
}
