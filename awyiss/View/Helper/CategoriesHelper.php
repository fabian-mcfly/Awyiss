<?php /** @noinspection HtmlUnknownTarget */

declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\Utility\Inflector;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;


/**
 * @property \Awyiss\View\Helper\FormHelper $Form
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 * @property \Awyiss\View\Helper\UrlHelper $Url
 */
class CategoriesHelper extends \Cake\View\Helper {
	use IdGeneratorTrait;
	use StringTemplateTrait;

	protected $_defaultConfig = [
		'templateClass' => \Awyiss\View\StringTemplate::class,
		'templates' => [
			'aggregationOption' => '<li{{attrs}}><a href="{{link}}">{{title}}</a></li>',
			'unassignedOption' => '<li{{attrs}}><a href="{{link}}">{{title}}</a></li>',
			'linkSelect' => '<div{{attrs}}><label class="Label">{{label}}: {{selectedOption}}</label><ul class="List">{{options}}</ul></div>',
			'option' => '<li{{attrs}}><a href="{{link}}">{{title}}</a></li>',
		],
	];
	protected array $defaultWidgets = [
		'linkSelect' => ['LinkSelect'],
	];
	protected WidgetLocator $widgetLocator;


	public $helpers = ['Form', 'Paginator', 'Url'];


	public function __construct (View $ao_view, array $aa_config = []) {
		$la_config = $aa_config;

		$la_widgets = $this->defaultWidgets;
		if (isset($la_config['widgets'])) {
			if (is_string($la_config['widgets'])) {
				$la_config['widgets'] = (array) $la_config['widgets'];
			}
			$la_widgets = $la_config['widgets'] + $la_widgets;
			unset($la_config['widgets']);
		}

		parent::__construct($ao_view, $la_config);

		$lo_widgetLocator = new WidgetLocator($this->templater(), $this->_View, $la_widgets);
		$this->setWidgetLocator($lo_widgetLocator);
	}


	/**
	 * Set the widget locator the helper will use.
	 *
	 * @param \Cake\View\Widget\WidgetLocator $ao_widgetLocator The locator instance to set.
	 * @return $this
	 */
	public function setWidgetLocator(WidgetLocator $ao_widgetLocator): self {
		$this->widgetLocator = $ao_widgetLocator;

		return $this;
	}


	/**
	 * Render a named widget.
	 *
	 * This is a lower level method. For built-in widgets, you should be using
	 * methods like `text`, `hidden`, and `radio`. If you are using additional
	 * widgets you should use this method render the widget without the label
	 * or wrapping div.
	 *
	 * @param string $as_name The name of the widget. e.g. 'text'.
	 * @param array $aa_data The data to render.
	 * @return string
	 */
	public function widget(string $as_name, array $aa_data = []): string {
		$la_data = $aa_data;

		$lo_widget = $this->widgetLocator->get($as_name);

		/**
		 * This call with $this->>Form->context() is hacky but since the WidgetInterface requires a ContextInterface,
		 * there's no way around it except setting an own context which is total overkill since this Helper doesn't require one itself.
		 * But hey, we're using the FormHelper and this has a context, so we don't care
		 *
		 * 		¯\_(ツ)_/¯
		 *
		 */
		return $lo_widget->render($la_data, $this->Form->context());
	}


	public function control (?string $as_fieldName = NULL, array $aa_attributes = []): string {
		$ls_fieldName = $as_fieldName ?? 'category';
		$la_attributes = $aa_attributes + [
			'empty' => TRUE,
		];

		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategoriesFromRequest($ls_fieldName, $la_attributes['categoriesName'] ?? 'category');
		}

		if ( ! array_key_exists('empty', $la_attributes)) {
			$la_attributes['empty'] = TRUE;
		}

		if ( ! array_key_exists('type', $la_attributes)) {
			$la_attributes['type'] = 'select';
		}

		return $this->Form->control($ls_fieldName, $la_attributes);
	}


	public function select (?string $as_fieldName = NULL, iterable $ax_options = [], array $aa_attributes = []): string {
		$ls_fieldName = $as_fieldName ?? 'category';
		$la_attributes = $aa_attributes + [
			'empty' => TRUE,
		];

		$lx_categories = $ax_options;
		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategoriesFromRequest($ls_fieldName, $la_attributes['categoriesName'] ?? 'category');
		}

		if ( ! array_key_exists('empty', $la_attributes)) {
			$la_attributes['empty'] = TRUE;
		}

		return $this->Form->select($ls_fieldName, $lx_categories, $la_attributes);
	}


	public function filter (?string $as_fieldName = NULL, iterable $ax_options = [], array $aa_attributes = []): ?string {
		$ls_fieldName = $as_fieldName ?? 'category';
		$la_attributes = $aa_attributes + [
			'aggregationLabel' => _('::' . $ls_fieldName . '_filter_all'),
			'aggregationKey' => 'all',
			'disabled' => FALSE,
			'escape' => TRUE,
			'label' => _('::' . Inflector::underscore($ls_fieldName) . '_filter_label'),
			'name' => Inflector::dasherize($ls_fieldName),
			'unassignedLabel' => _('::' . $ls_fieldName . '_filter_unassigned'),
			'unassignedKey' => 'unassigned',
			'val' => NULL,
		];

		if (isset($la_attributes['id']) && $la_attributes['id'] === true) {
			$la_attributes['id'] = $this->_domId($ls_fieldName);
		}

		$la_attributes['options'] = $ax_options;
		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategoriesFromRequest($ls_fieldName);
		}

		foreach ($la_attributes['options'] AS $lx_key => &$lx_value) {
			if (!is_array($lx_value)) {
				$lx_value = [
					'title' => $lx_value
				];
			}

			$lx_value['link'] = $this->Url->build([$la_attributes['name'] => $lx_key], ['withoutParams' => ['page']]);
		}
		unset($lx_value);

		if (empty($la_attributes['val'])) {
			$la_attributes['val'] = $this->getSelectedCategoryFromRequest($ls_fieldName);
		}

		return $this->widget('linkSelect', $la_attributes);
	}


	protected function getCategoriesFromRequest (...$aa_names): ?iterable {
		$lx_return = [];
		$la_categorization = $this->getView()->getRequest()->getAttribute('categorization', []);


		foreach ($aa_names AS $ls_name) {
			$lx_return = \Cake\Utility\Hash::get($la_categorization, $ls_name . '.categories.simple', []);

			if (!empty($lx_return)) {
				return $lx_return;
			}
		}

		return $lx_return;
	}


	protected function getSelectedCategoryFromRequest (...$aa_names): mixed {
		$la_categorization = $this->getView()->getRequest()->getAttribute('categorization', []);

		foreach ($aa_names AS $ls_name) {
			$lx_return = \Cake\Utility\Hash::get($la_categorization, $ls_name . '.selectedCategory');

			if (!is_null($lx_return)) {
				return $lx_return;
			}
		}

		return NULL;
	}
}