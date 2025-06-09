<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * MediaFolder Entity
 *
 * @property int $id
 * @property int|null $parentId
 * @property string|null $languageShortcode
 * @property string|null $path
 * @property string|null $title
 * @property bool $hidden
 * @property int $systemOrder
 * @property bool $active
 * @property bool $parentsActive
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\MediaFolder $parentMediaFolder
 * @property \Awyiss\Model\Entity\MediaFolder[] $childMediaFolders
 * @property \Awyiss\Model\Entity\Media[] $media
 * @property \Awyiss\Model\Entity\MediaAssignment[] $mediaAssignments
 * @property \Awyiss\Model\Entity\Language $language
 */
class MediaFolder extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'parentId' => true,
		'path' => true,
		'languageShortcode' => true,
		'title' => true,
		'hidden' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'language_shortcode' => 'languageShortcode',
		'system_order' => 'systemOrder',
		'parents_active' => 'parentsActive',
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent media folders of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $options);
	}


	/**
	 * Get all the parent media folders and all of its parents media folders of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $options, $currentLevel);
	}


	/**
	 * Make sure the path is always lowercase, dashed and free of special characters
	 *
	 * @param string|null $path
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Page::$path
	 */
	protected function _setPath(?string $path): ?string {
		if ($path === null) {
			return null;
		}

		if ($this->deleted) {
			return $path;
		}

		$ls_path = Text::slug($path, ['preserve' => '/']);
		$ls_path = trim($ls_path, '/');

		if (str_contains($ls_path, '/')) {
			$ls_path = substr($ls_path, strrpos($ls_path, '/') + 1);
		}


		return mb_strtolower($ls_path);
	}
}
