<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\View\StringTemplate;
use Cake\Collection\Collection;
use Cake\Collection\Iterator\TreeIterator;
use Cake\ORM\ResultSet;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;


/**
 * Helper class that provides methods related to the Categories-logic in the views
 *
 * @property FormHelper $Form
 * @property PaginatorHelper $Paginator
 * @property UrlHelper $Url
 */
class CategoriesHelper extends Helper {
	use IdGeneratorTrait;
	use StringTemplateTrait;


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'templateClass' => StringTemplate::class,
		'templates' => [
			'aggregationOption' => '<li{{attrs}}><a href="{{link}}">{{title}}</a></li>',
			'unassignedOption' => '<li{{attrs}}><a href="{{link}}">{{title}}</a></li>',
			'linkSelect' => '<div{{attrs}}><label class="Label">{{label}}: {{selectedOption}}</label><ul class="List">{{options}}</ul></div>',
			'option' => '<li{{attrs}}><a href="{{link}}">{{levelPrefix}}{{title}}</a></li>',
			'selectedOption' => '{{title}}',
		],
	];
	/**
	 * Default widgets
	 *
	 * @var array<string, array<string>>
	 */
	protected array $defaultWidgets = [
		'linkSelect' => ['LinkSelect'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form', 'Paginator', 'Url'];
	/**
	 * Locator for input widgets.
	 *
	 * @var WidgetLocator
	 */
	protected WidgetLocator $widgetLocator;


	/**
	 * @param View $ao_view
	 * @param array $aa_config
	 */
	public function __construct(View $ao_view, array $aa_config = []) {
		$la_config = $aa_config;

		//Add the default widgets to those that may be present in the provided config
		$la_widgets = $this->defaultWidgets;
		if (isset($la_config['widgets'])) {
			if (is_string($la_config['widgets'])) {
				$la_config['widgets'] = (array)$la_config['widgets'];
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
	 * @param WidgetLocator $ao_widgetLocator The locator instance to set.
	 * @return $this
	 */
	public function setWidgetLocator(WidgetLocator $ao_widgetLocator): static {
		$this->widgetLocator = $ao_widgetLocator;


		return $this;
	}


	/**
	 * Render a named widget.
	 *
	 * This is a lower level method. For built-in widgets, you should be using
	 * methods like `control`, `select`, and `filter`.
	 *
	 * @param string $as_name The name of the widget. e.g. 'control'.
	 * @param array $aa_data The data to render.
	 * @return string
	 */
	public function widget(string $as_name, array $aa_data = []): string {
		$la_data = $aa_data;

		$lo_widget = $this->widgetLocator->get($as_name);


		/**
		 * This call with $this->Form->context() is hacky but since the WidgetInterface requires a ContextInterface,
		 * there's no way around it; except setting an own context which is total overkill since this Helper doesn't require one itself.
		 * But hey, we're using the FormHelper and this has a context, so we don't care
		 *
		 *
		 *        ¯\_(ツ)_/¯
		 */
		return $lo_widget->render($la_data, $this->Form->context());
	}


	/**
	 * Generates a form control element complete with label and wrapper div.
	 * ### Options
	 * See each field type method for more information. Any options that are part of
	 * $attributes or $options for the different **type** methods can be included in `$options` for control().
	 * Additionally, any unknown keys that are not in the list below, or part of the selected type's options
	 * will be treated as a regular HTML attribute for the generated input.
	 * - `type` - Force the type of widget you want. e.g. `type => 'select'`
	 * - `label` - Either a string label, or an array of options for the label. See FormHelper::label().
	 * - `options` - For widgets that take options e.g. radio, select.
	 * - `error` - Control the error message that is produced. Set to `false` to disable any kind of error reporting
	 *   (field error and error messages).
	 * - `empty` - String or boolean to enable empty select box options.
	 * - `nestedInput` - Used with checkbox and radio inputs. Set false to render inputs outside of label
	 *   elements. Can be set to true on any input to force the input inside the label. If you
	 *   enable this option for radio buttons you will also need to modify the default `radioWrapper` template.
	 * - `templates` - The templates you want to use for this input. Any templates will be merged on top of
	 *   the already loaded templates. This option can either be a filename in /config that contains
	 *   the templates you want to load, or an array of templates to use.
	 * - `labelOptions` - Either `false` to disable label around nestedWidgets e.g. radio, multicheckbox or an array
	 *   of attributes for the label tag. `selected` will be added to any classes e.g. `class => 'myclass'` where
	 *   widget is checked
	 *
	 * @param string|null $as_fieldName
	 * @param array $aa_attributes
	 * @return string
	 * @see \Cake\View\Helper\FormHelper::control()
	 */
	public function control(?string $as_fieldName = null, array $aa_attributes = []): string {
		$ls_fieldName = $as_fieldName ?? 'category';
		$la_attributes = $aa_attributes + ['empty' => true, 'type' => 'select'];

		$ls_identifier = Inflector::variable($ls_fieldName);

		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategoriesFromRequest(true, $la_attributes['categoriesIdentifier'] ?? $ls_identifier, $ls_identifier, 'category') ?? [];
		}

		if ($la_attributes['options'] instanceof TreeIterator) {
			$la_attributes['options'] = $la_attributes['options']->printer(
				...(
					$la_attributes['printer'] ?? [
						'label',
						'id',
						$la_attributes['levelPrefix'] ?? '- ',
					]
				)
			);
		}
		elseif ($la_attributes['options'] instanceof Collection || $la_attributes['options'] instanceof ResultSet) {
			$la_attributes['options'] = $la_attributes['options']->combine(
				...(
					$la_attributes['combinator'] ?? [
						'id',
						'label',
						null,
					]
				)
			)->toArray();
		}


		return $this->Form->control($ls_fieldName, $la_attributes);
	}


	/**
	 * Returns a filter element, using the `linkSelect`-template.
	 *
	 * ### Options:
	 *
	 * - `aggregationLabel` The label of an additional option that's displayed when the `includeAggregation`-option is true.
	 * - `aggregationKey` The value of an additional option that's used for showing the aggregation of all categories.
	 * - `disabled` Boolean value or an array containing the values that should be disabled in the filter.
	 * - `escape` Boolean value whether to escape html entities.
	 * - `includeAggregation` Boolean value whether to include the aggregation of all categories.
	 * - `includeUnassigned` Boolean value whether to include an option to show items without any category.
	 * - `label` The label to display in the filter.
	 * - `name` The name to be used as a parameter in the resulting filter urls.
	 * - `templateVars` Additional template variables.
	 * - `unassignedLabel` The label of an additional option that's displayed when the `includeUnassigned`-option is true.
	 * - `unassignedKey` The value of an additional option that's used for showing items without any category.
	 * - `val` The value of the currently selected category.
	 *
	 * @param string|null $as_fieldName
	 * @param iterable $ax_options
	 * @param array $aa_attributes
	 * @return string
	 */
	public function filter(?string $as_fieldName = null, iterable $ax_options = [], array $aa_attributes = []): string {
		$ls_fieldName = $as_fieldName ?? 'category';
		$la_attributes = $aa_attributes + [
				'aggregationLabel' => __($ls_fieldName . '_filter_all'),
				'aggregationKey' => 'all',
				'disabled' => false,
				'escape' => true,
				'label' => __($ls_fieldName . '_filter_label'),
				'identifier' => $ls_identifier = Inflector::variable($ls_fieldName),
				'levelPrefix' => '- ',
				'unassignedLabel' => __($ls_fieldName . '_filter_unassigned'),
				'unassignedKey' => 'unassigned',
				'uriParam' => Inflector::dasherize($ls_fieldName),
				'val' => $this->getSelectedCategoryFromRequest($ls_identifier),
			];

		if (isset($la_attributes['id']) && $la_attributes['id'] === true) {
			$la_attributes['id'] = $this->_domId($ls_fieldName);
		}
		$la_attributes['options'] = $ax_options;
		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategoriesFromRequest(true, $la_attributes['identifier']) ?? [];
		}

		if ($la_attributes['options'] instanceof TreeIterator) {
			$la_attributes['options'] = $la_attributes['options']->toList();
			$la_attributes['options'] = array_combine(array_column($la_attributes['options'], 'id'), $la_attributes['options']);
		}
		elseif ($la_attributes['options'] instanceof Collection || $la_attributes['options'] instanceof ResultSet) {
			$la_attributes['options'] = $la_attributes['options']->combine(
				...(
					$la_attributes['combinator'] ?? [
						'id',
						'label',
						null,
					]
				)
			)->toArray();
		}

		foreach ($la_attributes['options'] as $lx_key => $lx_option) {
			if (is_object($lx_option)) {
				$la_data = [
					'id' => $lx_option->id,
					'title' => $lx_option->label ?? $lx_option->title,
					'link' => $this->Url->build([$la_attributes['uriParam'] => $lx_option->id], ['withoutParams' => ['page']]),
					'levelPrefix' => str_repeat($la_attributes['levelPrefix'], $lx_option->level ?? 0),
				];
				$la_attributes['options'][ $lx_key ] = $la_data;
			}
			elseif (!is_array($lx_option)) {
				$la_data = [
					'id' => null,
					'title' => $lx_option,
					'link' => $this->Url->build([$la_attributes['uriParam'] => $lx_key], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
				];

				$la_attributes['options'][ $lx_key ] = $la_data;
			}
		}

		$la_attributes['aggregationLink'] = $this->Url->build([$la_attributes['uriParam'] => $la_attributes['aggregationKey']], ['withoutParams' => ['page']]);
		$la_attributes['unassignedLink'] = $this->Url->build([$la_attributes['uriParam'] => $la_attributes['unassignedKey']], ['withoutParams' => ['page']]);


		return $this->widget('linkSelect', $la_attributes);
	}


	/**
	 * For a list of given names, return the first found `categories.raw` or `categories.simple`-value
	 * in the `categorization`-attribute of the request, depending on the $ab_preferRaw parameter
	 *
	 * @param bool $ab_preferRaw
	 * @param string ...$aa_names
	 * @return iterable|null
	 */
	public function getCategoriesFromRequest(bool $ab_preferRaw = true, string ...$aa_names): ?iterable {
		$la_categorization = $this->getView()->getRequest()->getAttribute('categorization', []);

		foreach ($aa_names as $ls_name) {
			if ($ab_preferRaw) {
				$lx_return = Hash::get($la_categorization, $ls_name . '.categories.raw', []);
			}

			//Empty means no prefered raw format or prefered raw but empty
			if (empty($lx_return)) {
				$lx_return = Hash::get($la_categorization, $ls_name . '.categories.simple', []);
			}

			if (!empty($lx_return)) {
				return $lx_return;
			}
		}


		return null;
	}


	/**
	 * For a list of given names, return the first found `selectedCategory`-value in the `categorization`-attribute of the request
	 *
	 * @param string ...$aa_names
	 * @return mixed
	 */
	public function getSelectedCategoryFromRequest(string ...$aa_names): mixed {
		$la_categorization = $this->getView()->getRequest()->getAttribute('categorization', []);

		foreach ($aa_names as $ls_name) {
			$lx_return = Hash::get($la_categorization, $ls_name . '.selectedCategory');

			if (!is_null($lx_return)) {
				return $lx_return;
			}
		}


		return null;
	}
}
