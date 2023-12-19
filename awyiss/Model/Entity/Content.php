<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Content Entity
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $text
 * @property int|null $media_id
 * @property int|null $media_alt_id
 * @property int|null $media_folders_id
 * @property string|null $link
 * @property \Cake\I18n\FrozenTime|null $publishdate_start
 * @property \Cake\I18n\FrozenTime|null $publishdate_end
 * @property string|null $template_position
 * @property int $content_templates_id
 * @property float $columnwidth
 * @property string|null $css_class
 * @property int|null $forms_id
 * @property int|null $duplicate_of
 * @property string|null $data
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int $parent_id
 * @property int $pages_id
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 * @property \Awyiss\Model\Entity\ContentTemplate $content_template
 * @property \Awyiss\Model\Entity\Content $parent_content
 * @property \Awyiss\Model\Entity\Page $page
 * @property \Awyiss\Model\Entity\Content[] $child_contents
 */
class Content extends \Awyiss\Model\Entity {
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
		'content_templates_id' => TRUE,
		'columnwidth' => TRUE,
		'css_class' => TRUE,
		'duplicate_of' => TRUE,
		'data' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
		'parent_id' => TRUE,
		'pages_id' => TRUE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _getCssClass (?array $aa_cssClass = NULL): ?array {
		return $aa_cssClass ? array_combine($aa_cssClass, $aa_cssClass) : NULL;
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setCssClass (mixed $ax_value): array {
		if (empty($ax_value)) {
			return [];
		}

		if (is_array($ax_value)) {
			return $ax_value;
		}

		return array_unique(array_map('trim', explode(',', trim($ax_value, ', '))));
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setData (mixed $ax_value): ?array {
		if (empty($ax_value)) {
			return NULL;
		}

		return is_array($ax_value) ? $ax_value : [$ax_value];
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getDirectChildren (): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getDirectChildren($this);
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getChildren (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getParent (): ?self {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParent($this);
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getParents (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}
}
