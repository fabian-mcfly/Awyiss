<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Table\ContentsTable;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;


/**
 * Content Entity
 *
 * @property int $id
 * @property int $pageId
 * @property int $parentId
 * @property string|NULL $title
 * @property string|NULL $subtitle
 * @property string|NULL $text
 * @property string|NULL $link
 * @property int $contentAreaId
 * @property ContentArea $contentArea
 * @property int $contentTemplateId
 * @property float $columnwidth
 * @property string|NULL $cssClass
 * @property int|NULL $duplicateOf
 * @property string|NULL $data
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
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
		'pageId' => TRUE,
		'parentId' => TRUE,
		'title' => TRUE,
		'subtitle' => TRUE,
		'text' => TRUE,
		'link' => TRUE,
		'contentAreaId' => TRUE,
		'contentTemplateId' => TRUE,
		'columnwidth' => TRUE,
		'cssClass' => TRUE,
		'duplicateOf' => TRUE,
		'data' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
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
	public function getChildren(): ?CollectionInterface {
		/** @var ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(): ?self {
		/** @var ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}


	protected function _setData(array $aa_data) {
		if (empty($aa_data)) {
			return NULL;
		}


		return $aa_data;
	}
}
