<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Content Entity
 *
 * @property int $id
 * @property string|NULL $title
 * @property string|NULL $subtitle
 * @property string|NULL $text
 * @property int|NULL $media_id
 * @property int|NULL $media_alt_id
 * @property int|NULL $media_folders_id
 * @property string|NULL $link
 * @property \Cake\I18n\FrozenTime|NULL $publishdate_start
 * @property \Cake\I18n\FrozenTime|NULL $publishdate_end
 * @property string|NULL $template_position
 * @property int $content_template_id
 * @property float $columnwidth
 * @property string|NULL $css_class
 * @property int|NULL $forms_id
 * @property int|NULL $duplicate_of
 * @property string|NULL $data
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int $parent_id
 * @property int $page_id
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 * @property \Awyiss\Model\Entity\ContentTemplate $content_template
 * @property \Awyiss\Model\Entity\Content $parent_content
 * @property \Awyiss\Model\Entity\Page $page
 * @property \Awyiss\Model\Entity\Content[] $child_contents
 */
class Content extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'subtitle' => TRUE,
		'text' => TRUE,
		'link' => TRUE,
		'publishdate_start' => TRUE,
		'publishdate_end' => TRUE,
		'template_position' => TRUE,
		'content_template_id' => TRUE,
		'columnwidth' => TRUE,
		'css_class' => TRUE,
		'duplicate_of' => TRUE,
		'data' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
		'parent_id' => TRUE,
		'page_id' => TRUE,
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getDirectChildren (): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getDirectChildren($this);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent (): ?self {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParent($this);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}
}
