<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Content Entity
 *
 * @property int $id
 * @property int $pageId
 * @property int $parentId
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $text
 * @property string|null $link
 * @property int $contentAreaId
 * @property ContentArea $contentArea
 * @property int $contentTemplateId
 * @property float $columnwidth
 * @property string|null $cssClass
 * @property int|null $duplicateOf
 * @property string|null $data
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property ContentTemplate $contentTemplate
 * @property Content $parentContent
 * @property Page $page
 * @property Content[] $childContents
 * @property Content[] $duplicateContents
 * @property Content $duplicateOfContent
 */
class Content extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageId' => true,
		'parentId' => true,
		'title' => true,
		'subtitle' => true,
		'text' => true,
		'link' => true,
		'contentAreaId' => true,
		'contentTemplateId' => true,
		'columnwidth' => true,
		'cssClass' => true,
		'duplicateOf' => true,
		'data' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected static array $fieldMap = [
		'page_id' => 'pageId',
		'parent_id' => 'parentId',
		'content_area_id' => 'contentAreaId',
		'content_template_id' => 'contentTemplateId',
		'css_class' => 'cssClass',
		'duplicate_of' => 'duplicateOf',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'content_area' => 'contentArea',
		'content_template' => 'contentTemplate',
		'child_contents' => 'childContents',
		'parent_content' => 'parentContent',
		'duplicate_contents' => 'duplicateContents',
		'duplicate_of_content' => 'duplicateOfContent',
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $aa_options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $aa_options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $aa_options = []): ?self {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $aa_options);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Make sure no empty array finds its way into the db
	 *
	 * @param array $aa_data
	 * @return array|null
	 * @noinspection PhpUnused
	 */
	protected function _setData(?array $aa_data = null): ?array {
		if (empty($aa_data)) {
			return null;
		}


		return $aa_data;
	}
}
