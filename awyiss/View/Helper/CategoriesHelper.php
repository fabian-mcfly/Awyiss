<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\View\StringTemplate;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;
use RuntimeException;


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
	 * @noinspection HtmlUnknownTarget
	 */
	protected array $_defaultConfig = [
		'templateClass' => StringTemplate::class,
		'templates' => [
			'linkSelect' => '<div{{attrs}}><label class="Label" tabindex="0"><strong>{{label}}:</strong> {{selectedOption}}</label><ul class="List">{{options}}</ul></div>',
			'option' => '<li{{attrs}}><a href="{{link}}" title="{{title}}">{{levelPrefix}}{{title}}</a></li>',
			'optionDisabled' => '<li{{attrs}}>{{levelPrefix}}{{title}}</li>',
			'groupLabel' => '<li{{attrs}} title="{{title}}"><strong>{{title}}</strong></li>',
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
	 * @param string|null $as_identifier
	 * @param array $aa_attributes
	 * @return string
	 * @see \Cake\View\Helper\FormHelper::control()
	 */
	public function control(string $as_identifier, array $aa_attributes = []): string {
		$ls_identifier = Inflector::underscore($as_identifier);
		$la_config = $this->getConfiguration($ls_identifier);

		if (empty($la_config) || !$la_config['enabled']) {
			return '';
		}

		$ls_fieldName = Inflector::underscore($la_config['fieldname']);

		$la_config = ['fieldname' => $ls_fieldName] + $la_config;
		$la_attributes = $aa_attributes + [
			'isCategory' => true,
			'empty' => false,
			'groupBy' => false,
			'groupLabels' => [],
			'label' => $this->Form->labelTextFromFieldname($ls_fieldName),
			'type' => 'select',
		];


		if (!isset($la_attributes['val'])) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_attributes['val'] = $this->Form->context()->entity()->get($ls_fieldName);
		}

		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategories($la_config['identifier'], true);
		}

		$lx_options = $la_attributes['options'];
		unset($la_attributes['options']);
		if ($lx_options) {
			$ls_groupBy = $la_attributes['groupBy'] ?? null;

			if (!$ls_groupBy && !empty($la_config['includeParentCategories'])) {
				$ls_groupBy = '_parents';
			}

			if ($ls_groupBy) {
				if (!$lx_options instanceof CollectionInterface) {
					$lx_options = collection($lx_options);
				}

				$lx_options = $lx_options->groupBy(function (mixed $ax_element) use ($ls_groupBy) {
					if ($ax_element instanceof EntityInterface) {
						if (is_array($ax_element->$ls_groupBy)) {
							return implode(' - ', array_map(function (EntityInterface $ao_entity) {
								/** @noinspection PhpPossiblePolymorphicInvocationInspection */
								return $ao_entity->label;
							}, $ax_element->$ls_groupBy));
						}


						return $ax_element->$ls_groupBy ?? '';
					}


					return $ax_element[ $ls_groupBy ] ?? '';
				});

				$la_attributes['options'] = [];
				foreach ($lx_options as $lx_key => $la_options) {
					$ls_groupLabel = $lx_key ?: 'general';
					if (isset($la_attributes['groupLabels'][ $ls_groupLabel ])) {
						$ls_groupLabel = $la_attributes['groupLabels'][ $ls_groupLabel ];
					}

					$la_attributes['options'][ $ls_groupLabel ] = $this->formatOptions($la_options, $la_attributes + ['buildNested' => true]);
				}
			}
			else {
				$la_attributes['options'] = $this->formatOptions($lx_options, $la_attributes);
			}
		}

		unset($la_attributes['groupLabels']);


		return $this->Form->control($ls_fieldName, $la_attributes);
	}


	/**
	 * Returns a filter element, using the `linkSelect`-template.
	 *
	 * ### Options:
	 * - `aggregationLabel` The label of an additional option that's displayed when the `allowAggregation`-option is true.
	 * - `aggregationKey` The value of an additional option that's used for showing the aggregation of all categories.
	 * - `allowAggregation` Boolean value whether to include the aggregation of all categories.
	 * - `allowUnassigned` Boolean value whether to include an option to show items without any category.
	 * - `disabled` Boolean value or an array containing the values that should be disabled in the filter.
	 * - `escape` Boolean value whether to escape html entities.
	 * - `label` The label to display in the filter.
	 * - `name` The name to be used as a parameter in the resulting filter urls.
	 * - `templateVars` Additional template variables.
	 * - `unassignedLabel` The label of an additional option that's displayed when the `allowUnassigned`-option is true.
	 * - `unassignedKey` The value of an additional option that's used for showing items without any category.
	 * - `val` The value of the currently selected category.
	 *
	 * @param string|null $as_identifier
	 * @param iterable $ax_options
	 * @param array $aa_attributes
	 * @return string
	 */
	public function filter(string $as_identifier, ?iterable $ax_options = null, array $aa_attributes = []): string {
		$ls_identifier = Inflector::underscore($as_identifier);
		$la_config = $this->getConfiguration($ls_identifier);

		if (empty($la_config) || !$la_config['enabled']) {
			return '';
		}

		$ls_fieldName = Inflector::underscore($la_config['fieldname']);

		$la_attributes = $aa_attributes + $la_config;
		$la_attributes += [
			'aggregationLabel' => __($ls_fieldName . '_filter_all'),
			'disabled' => false,
			'escape' => true,
			'id' => true,
			'label' => __($ls_identifier . '_filter_label'),
			'levelPrefix' => '- ',
			'unassignedLabel' => __($ls_identifier . '_filter_unassigned'),
			'val' => $this->getSelectedCategory($ls_identifier),
		];

		if (isset($la_attributes['id']) && $la_attributes['id'] === true) {
			$la_attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($ls_identifier), '-');
		}

		$la_attributes['options'] = $ax_options;
		if (!$la_attributes['options']) {
			$la_attributes['options'] = $this->getCategories($ls_identifier, true);
		}

		$la_attributes = $this->buildOptions($la_attributes, $la_config);


		return $this->widget('linkSelect', $la_attributes);
	}


	/**
	 * @param string $as_identifier
	 * @param iterable|null $ax_options
	 * @param array $aa_attributes
	 * @return string
	 */
	public function linkSelect(string $as_identifier, ?iterable $ax_options = null, array $aa_attributes = []): string {
		$la_attributes = $aa_attributes;
		$la_attributes += [
			'disabled' => false,
			'escape' => true,
			'id' => true,
			'label' => __($as_identifier . '_filter_label'),
			'levelPrefix' => '- ',
			'uriParam' => Inflector::variable($as_identifier),
			'val' => null,
		];

		if (isset($la_attributes['id']) && $la_attributes['id'] === true) {
			$la_attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($as_identifier), '-');
		}

		$la_attributes['options'] = $ax_options;
		$la_attributes = $this->buildOptions($la_attributes);


		return $this->widget('linkSelect', $la_attributes);
	}


	/**
	 * @param string $as_identifier
	 * @return array
	 */
	public function getConfiguration(string $as_identifier): array {
		$ls_name = Inflector::variable(Inflector::pluralize($as_identifier));
		$la_categories = $this->getView()->get($ls_name);

		if ($la_categories instanceof PaginatedResultSet) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_categories = $la_categories->items()->toArray();
		}

		return $la_categories['config'] ?? [];
	}


	/**
	 * For a list of given names, return the first found `categories.raw` or `categories.simple`-value in the `categories`-view var
	 *
	 * @param string $as_identifier
	 * @param bool $ab_preferRaw
	 * @return iterable|null
	 */
	public function getCategories(string $as_identifier, bool $ab_preferRaw = false): ?iterable {
		$ls_name = Inflector::variable(Inflector::pluralize($as_identifier));
		$la_categories = $this->getView()->get($ls_name);

		if ($ab_preferRaw) {
			$lx_return = $la_categories['raw'];
		}

		//Empty means no prefered raw format or prefered raw but empty
		if (empty($lx_return)) {
			$lx_return = $la_categories['simple'];
		}

		return $lx_return ?? [];
	}


	/**
	 * For a list of given names, return the first found `selectedCategory`-value in the `categories`-view va
	 *
	 * @param string $as_identifier
	 * @return mixed
	 */
	public function getSelectedCategory(string $as_identifier): mixed {
		$ls_name = Inflector::variable(Inflector::pluralize($as_identifier));
		$la_categories = $this->getView()->get($ls_name);

		return $la_categories['selected'] ?? null;
	}


	/**
	 * @param array $aa_attributes
	 * @param array $aa_config
	 * @return array
	 */
	protected function buildOptions(array $aa_attributes, array $aa_config = []): array {
		$la_attributes = $aa_attributes;
		$la_attributes += [
			'allowUnassigned' => false,
			'allowAggregation' => false,
		];

		$lx_options = $la_attributes['options'];
		unset($la_attributes['options']);

		if ($lx_options) {
			$ls_groupBy = $la_attributes['groupBy'] ?? null;

			if (!$ls_groupBy && !empty($aa_config['includeParentCategories'])) {
				$ls_groupBy = '_parents';
			}

			if ($ls_groupBy) {
				if (!$lx_options instanceof CollectionInterface) {
					$lx_options = collection($lx_options);
				}

				$la_options = $lx_options->groupBy(function (mixed $ax_element) use ($ls_groupBy, &$la_attributes) {
					if ($ax_element instanceof EntityInterface) {
						if (is_array($ax_element->$ls_groupBy)) {
							$ls_path = implode(' - ', array_map(function (EntityInterface $ao_entity) {
								/** @noinspection PhpPossiblePolymorphicInvocationInspection */
								return $ao_entity->label;
							}, $ax_element->$ls_groupBy));

							$la_attributes['groupLabels'][ $ls_path ] ??= $ls_path;


							return $ls_path;
						}


						return $ax_element->$ls_groupBy ?? '';
					}


					return $ax_element[ $ls_groupBy ] ?? '';
				})->toArray();

				$la_attributes['options'] = [];
				foreach ($la_options as $lx_key => $lx_options) {
					$la_attributes['options'][] = [
						'id' => null,
						'title' => $lx_key,
						'link' => null,
						'levelPrefix' => null,
						'isGroupLabel' => true,
					];
					$la_attributes['options'] += $this->formatOptions($lx_options, $la_attributes + ['isGrouped' => true], true);
				}
			}
			else {
				$la_attributes['options'] = $this->formatOptions($lx_options, $la_attributes, true);
			}
		}

		//Shall an option to select unassigned elements be included? Prepend it.
		if ($la_attributes['allowUnassigned']) {
			$la_attributes['options'] = [
				$la_attributes['unassignedKey'] => [
					'id' => null,
					'title' => $la_attributes['unassignedLabel'],
					'link' => $this->Url->build([
						'_name' => $la_attributes['routeName'] ?? null,
						$la_attributes['uriParam'] => $la_attributes['unassignedKey'],
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
				],
			] + $la_attributes['options'];
		}

		//Shall an option to select unassigned elements be included? Prepend it.
		if ($la_attributes['allowAggregation']) {
			$la_attributes['options'] = [
				$la_attributes['aggregationKey'] => [
					'id' => null,
					'title' => $la_attributes['aggregationLabel'],
					'link' => $this->Url->build([
						'_name' => $la_attributes['routeName'] ?? null,
						$la_attributes['uriParam'] => $la_attributes['aggregationKey'],
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
				],
			] + $la_attributes['options'];
		}


		return $la_attributes;
	}


	/**
	 * @param array $ax_options
	 * @param array $aa_attributes
	 * @param bool $ab_forLinkSelect
	 * @return array
	 */
	protected function formatOptions(iterable $ax_options, array $aa_attributes, bool $ab_forLinkSelect = false): array {
		if ($ax_options instanceof TreeIterator) {
			if ($ab_forLinkSelect) {
				$la_options = $ax_options->toList();
				$la_options = array_combine(array_column($la_options, 'id'), $la_options);
			}
			else {
				$la_options = $ax_options->printer(
					...($aa_attributes['printer'] ?? [
						'label',
						'id',
						$aa_attributes['levelPrefix'] ?? '- ',
					])
				)->toArray();
			}
		}
		elseif ($ax_options instanceof CollectionInterface) {
			$la_combinator = array_values($aa_attributes['combinator'] ?? [
				'id',
				'label',
				null,
			]);

			$la_options = [];
			foreach ($ax_options as $lx_option) {
				$ls_title = $lx_option->{$la_combinator[1]};

				if ($lx_option->level ?? null) {
					$ls_title = str_repeat($aa_attributes['levelPrefix'] ?? '- ', $lx_option->level) . $ls_title;
				}

				$la_options[ $lx_option->{$la_combinator[0]} ] = $ls_title;
			}
		}
		elseif (is_array($ax_options)) {
			$la_options = $ax_options;

			if ($aa_attributes['buildNested'] ?? null === true) {
				$la_options = $this->formatOptions(collection($ax_options), $aa_attributes, $ab_forLinkSelect);
			}
		}
		else {
			throw new RuntimeException(sprintf('Cannot build options for type `%s`.', gettype($ax_options)));
		}

		if (!$ab_forLinkSelect) {
			return $la_options;
		}

		$la_formattedOptions = [];
		foreach ($la_options as $lx_key => $lx_option) {
			if (is_object($lx_option)) {
				$la_data = [
					'id' => $lx_option->id,
					'title' => $lx_option->label ?? $lx_option->title,
					'link' => $this->Url->build([
						'_name' => $aa_attributes['routeName'] ?? null,
						$aa_attributes['uriParam'] => $lx_option->id,
					], ['withoutParams' => ['page']]),
					'levelPrefix' => str_repeat($aa_attributes['levelPrefix'] ?? '', $lx_option->level ?? 0),
				];
				$la_formattedOptions[ $lx_option->id ] = $la_data;
			}
			elseif (is_array($lx_option)) {
				$la_formattedOptions[ $lx_option['id'] ?? $lx_key ] = $lx_option;
			}
			else {
				$la_data = [
					'id' => null,
					'title' => $lx_option,
					'link' => $this->Url->build([
						'_name' => $aa_attributes['routeName'] ?? null,
						$aa_attributes['uriParam'] => $lx_key,
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
				];

				$la_formattedOptions[ $lx_key ] = $la_data;
			}
		}


		return $la_formattedOptions;
	}
}
