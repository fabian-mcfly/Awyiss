<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * ContentTemplate Entity
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $fileName
 * @property bool $inContentRow
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Content[] $contents
 * @property \Awyiss\Model\Entity\ContentTemplateElement[] $contentTemplateElements
 * @property \Awyiss\Model\Entity\ContentArea[] $contentAreas
 */
class ContentTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'fileName' => true,
		'inContentRow' => true,
		'systemOrder' => true,
		'active' => true,
		'contentTemplateElements' => true,
		'contentAreas' => true,
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $fileName
	 * @return string|null
	 * @see \Awyiss\Model\Entity\ContentTemplate::$filename
	 */
	protected function _setFileName(?string $fileName): ?string {
		if ($fileName === null) {
			return null;
		}

		$fileName = Text::slug($fileName, ['replacement' => '_']);


		return mb_strtolower($fileName);
	}
}
