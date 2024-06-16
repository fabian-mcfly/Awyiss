<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * WidgetTemplate Entity
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
 * @property \Awyiss\Model\Entity\Widget[] $widgets
 * @property \Awyiss\Model\Entity\WidgetTemplateElement[] $widgetTemplateElements
 */
class WidgetTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'fileName' => true,
		'inContentRow' => true,
		'systemOrder' => true,
		'active' => true,
		'widgetTemplateElements' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'file_name' => 'fileName',
		'widget_template_elements' => 'widgetTemplateElements',
		'in_content_row' => 'inContentRow',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'used_for_widgets' => 'usedForWidgets',
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $fileName
	 * @return string|null
	 * @see \Awyiss\Model\Entity\WidgetTemplate::$filename
	 */
	protected function _setFileName(?string $fileName): ?string {
		if ($fileName === null) {
			return null;
		}

		$ls_fileName = Text::slug($fileName, ['replacement' => '_']);


		return mb_strtolower($ls_fileName);
	}
}
