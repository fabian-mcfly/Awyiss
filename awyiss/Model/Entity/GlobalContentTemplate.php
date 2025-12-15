<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * GlobalContentTemplate Entity
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
 * @property \Awyiss\Model\Entity\GlobalContent[] $globalContents
 * @property \Awyiss\Model\Entity\GlobalContentTemplateElement[] $globalContentTemplateElements
 */
class GlobalContentTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'file_name' => 'fileName',
		'global_content_template_elements' => 'globalContentTemplateElements',
		'in_content_row' => 'inContentRow',
		'system_order' => 'systemOrder',
		'used_for_global_contents' => 'usedForGlobalContents',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'fileName' => true,
		'inContentRow' => true,
		'systemOrder' => true,
		'active' => true,
		'globalContentTemplateElements' => true,
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $fileName
	 * @return string|null
	 * @see \Awyiss\Model\Entity\GlobalContentTemplate::$filename
	 */
	protected function _setFileName(?string $fileName): ?string {
		if ($fileName === null) {
			return null;
		}

		$fileName = Text::slug($fileName, ['replacement' => '_']);


		return mb_strtolower($fileName);
	}
}
