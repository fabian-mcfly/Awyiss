<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Utility\Inflector;
use Awyiss\View\StringTemplate;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\View\Helper;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\StringTemplateTrait;
use Cake\View\View;
use Cake\View\Widget\WidgetLocator;
use RuntimeException;


/**
 * Helper class that provides methods related to the Categories-logic in the views
 *
 * @property \Awyiss\View\Helper\FormHelper $Form
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 * @property \Awyiss\View\Helper\UrlHelper $Url
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
			'option' => '<li{{attrs}} title="{{title}}"><a href="{{link}}" title="{{title}}">{{levelPrefix}}{{title}}</a></li>',
			'optionDisabled' => '<li{{attrs}} title="{{title}}">{{levelPrefix}}{{title}}</li>',
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
	protected array $helpers = ['Form', 'Url'];
	/**
	 * Locator for input widgets.
	 *
	 * @var WidgetLocator
	 */
	protected WidgetLocator $widgetLocator;


	/**
	 * @param View $view
	 * @param array $config
	 */
	public function __construct(View $view, array $config = []) {
		$la_config = $config;

		//Add the default widgets to those that may be present in the provided config
		$la_widgets = $this->defaultWidgets;
		if (isset($la_config['widgets'])) {
			if (is_string($la_config['widgets'])) {
				$la_config['widgets'] = (array)$la_config['widgets'];
			}
			$la_widgets = $la_config['widgets'] + $la_widgets;
			unset($la_config['widgets']);
		}

		parent::__construct($view, $la_config);

		$lo_widgetLocator = new WidgetLocator($this->templater(), $this->_View, $la_widgets);
		$this->setWidgetLocator($lo_widgetLocator);
	}


	/**
	 * Get the widget locator.
	 *
	 * @return \Cake\View\Widget\WidgetLocator
	 */
	public function getWidgetLocator(): WidgetLocator {
		return $this->widgetLocator;
	}


	/**
	 * Set the widget locator the helper will use.
	 *
	 * @param WidgetLocator $widgetLocator The locator instance to set.
	 * @return $this
	 */
	public function setWidgetLocator(WidgetLocator $widgetLocator): static {
		$this->widgetLocator = $widgetLocator;


		return $this;
	}


	/**
	 * Render a named widget.
	 *
	 * This is a lower level method. For built-in widgets, you should be using
	 * methods like `control`, `select`, and `filter`.
	 *
	 * @param string $name The name of the widget. e.g. 'control'.
	 * @param array $data The data to render.
	 * @return string
	 */
	public function widget(string $name, array $data = []): string {
		$la_data = $data;

		$lo_widget = $this->getWidgetLocator()->get($name);


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
	 * - `groupBy` - If set to a field name, the options will be grouped by the value of that field.
	 * Provide the `groupLabels` option to customize the labels of the groups.
	 * - `groupLabels` - An array of group labels. The keys are the group values, the values are the labels.
	 *
	 * @param string|null $identifier
	 * @param array $attributes
	 * @return string
	 * @throws \Exception
	 * @see \Cake\View\Helper\FormHelper::control()
	 */
	public function control(string $identifier, array $attributes = []): string {
		$ls_identifier = Inflector::underscore($identifier);
		$la_config = $this->getConfiguration($ls_identifier);

		if (empty($la_config) || !$la_config['enabled']) {
			return '';
		}

		if (empty($la_config['field'])) {
			throw new RuntimeException('Cannot build categories control without field.');
		}

		if (empty($la_config['identifier'])) {
			$la_config['identifier'] = $ls_identifier;
		}

		$ls_fieldName = Inflector::underscore($la_config['field']);

		$la_config = ['field' => $ls_fieldName] + $la_config;
		$la_attributes = $attributes + [
			'isCategory' => true,
			'disabled' => false,
			'empty' => false,
			'groupBy' => null,
			'groupLabels' => [],
			'label' => $this->Form->labelTextFromFieldname($ls_fieldName),
			'type' => 'select',
			'val' => null,
		];

		if (!isset($la_attributes['val'])) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_attributes['val'] = $this->Form->context()->entity()->get($ls_fieldName) ?? $this->getSelectedCategory($ls_identifier);
		}

		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getCategories($la_config['identifier'], true);
		}

		$lx_options = $la_attributes['options'];
		unset($la_attributes['options']);
		if ($lx_options) {
			$ls_groupBy = $la_attributes['groupBy'] ?? null;

			if ($ls_groupBy === null && ($la_config['includeParentCategories'] ?? false) === true) {
				$ls_groupBy = '_parents';
			}

			if ($ls_groupBy) {
				if (!$lx_options instanceof CollectionInterface) {
					$lx_options = collection($lx_options);
				}

				$la_groupedOptions = $this->groupOptions($lx_options, $ls_groupBy, $la_attributes);

				$la_attributes['options'] = [];
				foreach ($la_groupedOptions as $lx_key => $lx_options) {
					$ls_groupLabel = $lx_key ?: 'general';
					$ls_groupLabel = $la_attributes['groupLabels'][ $ls_groupLabel ] ?? __($ls_fieldName . '_grouplabel_' . $ls_groupLabel);

					$la_attributes['options'][ $ls_groupLabel ] = $this->formatOptions($lx_options, $la_attributes + ['buildNested' => true]);
				}
			}
			else {
				$la_attributes['options'] = $this->formatOptions($lx_options, $la_attributes);
			}
		}

		unset($la_attributes['groupBy']);
		unset($la_attributes['groupLabels']);

		return $this->Form->control($ls_fieldName, $la_attributes);
	}


	/**
	 * Returns a filter element, using the `linkSelect`-template with the
	 * category config set as view vars
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
	 * @param string|null $identifier
	 * @param iterable $options
	 * @param array $attributes
	 * @return string
	 */
	public function filter(string $identifier, ?iterable $options = null, array $attributes = []): string {
		$ls_identifier = Inflector::underscore($identifier);
		$la_config = $this->getConfiguration($ls_identifier);

		if (empty($la_config) || !$la_config['enabled']) {
			return '';
		}

		$la_attributes = $attributes + $la_config;
		$la_attributes += [
			'aggregationLabel' => __($ls_identifier . '_filter_all'),
			'aggregationKey' => 'all',
			'allowAggregation' => false,
			'allowUnassigned' => false,
			'disabled' => false,
			'escape' => true,
			'id' => true,
			'identifier' => $ls_identifier,
			'label' => __($ls_identifier . '_filter_label'),
			'levelPrefix' => '- ',
			'unassignedLabel' => __($ls_identifier . '_filter_unassigned'),
			'unassignedKey' => 'unassigned',
			'uriParam' => $ls_identifier,
			'val' => $this->getSelectedCategory($ls_identifier),
		];

		if (isset($la_attributes['id']) && $la_attributes['id'] === true) {
			$la_attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($ls_identifier), '-');
		}

		$la_attributes['options'] = $options;
		if (!$la_attributes['options']) {
			$la_attributes['options'] = $this->getCategories($ls_identifier, true);
		}

		$la_attributes = $this->buildOptions($la_attributes, $la_config);

		return $this->widget('linkSelect', $la_attributes);
	}


	/**
	 * Creates a link select element using the `linkSelect`-template
	 * with only the provided options and attributes
	 *
	 *  ### Options:
	 *  - `aggregationLabel` The label of an additional option that's displayed when the `allowAggregation`-option is true.
	 *  - `aggregationKey` The value of an additional option that's used for showing the aggregation of all categories.
	 *  - `allowAggregation` Boolean value whether to include the aggregation of all categories.
	 *  - `allowUnassigned` Boolean value whether to include an option to show items without any category.
	 *  - `disabled` Boolean value or an array containing the values that should be disabled in the filter.
	 *  - `escape` Boolean value whether to escape html entities.
	 *  - `label` The label to display in the filter.
	 *  - `name` The name to be used as a parameter in the resulting filter urls.
	 *  - `templateVars` Additional template variables.
	 *  - `unassignedLabel` The label of an additional option that's displayed when the `allowUnassigned`-option is true.
	 *  - `unassignedKey` The value of an additional option that's used for showing items without any category.
	 *  - `val` The value of the currently selected category.
	 *
	 * @param string $identifier
	 * @param iterable|null $options
	 * @param array $attributes
	 * @return string
	 */
	public function linkSelect(string $identifier, ?iterable $options = null, array $attributes = []): string {
		$ls_identifier = Inflector::underscore($identifier);

		$la_attributes = $attributes;
		$la_attributes += [
			'aggregationLabel' => __($ls_identifier . '_filter_all'),
			'aggregationKey' => 'all',
			'allowAggregation' => false,
			'allowUnassigned' => false,
			'disabled' => false,
			'escape' => true,
			'id' => true,
			'identifier' => $ls_identifier,
			'label' => __($ls_identifier . '_filter_label'),
			'levelPrefix' => '- ',
			'unassignedLabel' => __($ls_identifier . '_filter_unassigned'),
			'unassignedKey' => 'unassigned',
			'uriParam' => $ls_identifier,
			'val' => null,
		];

		if (isset($la_attributes['id']) && $la_attributes['id'] === true) {
			$la_attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($ls_identifier), '-');
		}

		$la_attributes['options'] = $options;
		$la_attributes = $this->buildOptions($la_attributes);

		return $this->widget('linkSelect', $la_attributes);
	}


	/**
	 * @param string $identifier
	 * @return array
	 */
	public function getConfiguration(string $identifier): array {
		$ls_name = Inflector::variable(Inflector::pluralize($identifier));
		$la_categories = $this->getView()->get('_categories', [])[ $ls_name ] ?? [];

		if ($la_categories instanceof PaginatedResultSet) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_categories = $la_categories->items()->toArray();
		}

		return $la_categories['config'] ?? [];
	}


	/**
	 * For a list of given names, return the first found `categories.raw` or `categories.simple`-value in the `categories`-view var
	 *
	 * @param string $identifier
	 * @param bool $preferRaw
	 * @return iterable|null
	 */
	public function getCategories(string $identifier, bool $preferRaw = false): ?iterable {
		$ls_name = Inflector::variable(Inflector::pluralize($identifier));
		$la_categories = $this->getView()->get('_categories', [])[ $ls_name ] ?? [];

		if ($preferRaw) {
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
	 * @param string $identifier
	 * @return mixed
	 */
	public function getSelectedCategory(string $identifier): mixed {
		$ls_name = Inflector::variable(Inflector::pluralize($identifier));
		$la_categories = $this->getView()->get('_categories', [])[ $ls_name ] ?? [];

		return $la_categories['selected'] ?? null;
	}


	/**
	 * @param array $attributes
	 * @param array $config
	 * @return array
	 */
	protected function buildOptions(array $attributes, array $config = []): array {
		$la_attributes = $attributes;
		$la_attributes += [
			'allowUnassigned' => false,
			'allowAggregation' => false,
		];

		$lx_options = $la_attributes['options'];
		$la_attributes['options'] = [];

		if ($lx_options) {
			$ls_groupBy = $la_attributes['groupBy'] ?? null;

			if ($ls_groupBy === null && ($config['includeParentCategories'] ?? false) === true) {
				$ls_groupBy = '_parents';
			}

			if ($ls_groupBy) {
				if (!$lx_options instanceof CollectionInterface) {
					$lx_options = collection($lx_options);
				}

				$la_groupedOptions = $this->groupOptions($lx_options, $ls_groupBy, $la_attributes);

				foreach ($la_groupedOptions as $lx_key => $lx_options) {
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
	 * @param array $options
	 * @param array $attributes
	 * @param bool $forLinkSelect
	 * @return array
	 */
	protected function formatOptions(iterable $options, array $attributes, bool $forLinkSelect = false): array {
		if ($options instanceof TreeIterator) {
			$la_options = $this->formatTreeOptions($options, $attributes, $forLinkSelect);
		}
		elseif ($options instanceof CollectionInterface) {
			$la_options = $this->formatCollectionOptions($options, $attributes);
		}
		elseif (is_array($options)) {
			$la_options = $this->formatArrayOptions($options, $attributes, $forLinkSelect);
		}
		else {
			throw new RuntimeException(sprintf('Cannot build options for type `%s`.', gettype($options)));
		}

		if (!$forLinkSelect) {
			return $la_options;
		}

		return $this->formatOptionAttributes($la_options, $attributes);
	}


	/**
	 * @param array $options
	 * @param array $attributes
	 * @param bool $forLinkSelect
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function formatArrayOptions(array $options, array $attributes, bool $forLinkSelect): array {
		$la_options = $options;

		if (($attributes['buildNested'] ?? null) === true) {
			$la_options = $this->formatOptions(collection($options), $attributes, $forLinkSelect);
		}

		if (!in_array($attributes['groupBy'] ?? null, ['label', 'title', 'value', 'id'])) {
			array_walk($la_options, function (mixed &$option) use ($attributes): void {
				if (is_array($option)) {
					unset($option[ $attributes['groupBy'] ]);
				}
			});
		}

		return $la_options;
	}


	/**
	 * @param \Cake\Collection\CollectionInterface $options
	 * @param array $attributes
	 * @return array
	 */
	protected function formatCollectionOptions(CollectionInterface $options, array $attributes): array {
		$la_combinator = array_values($attributes['combinator'] ?? ['id', 'label', null]);

		$la_options = [];
		foreach ($options as $lx_option) {
			if ($lx_option instanceof EntityInterface) {
				$lx_option = $lx_option->extract([], false, false);
			}

			$ls_title = $lx_option[ $la_combinator[1] ] ?? $lx_option['label'] ?? $lx_option['title'] ?? $lx_option['value'] ?? $lx_option['id'];

			if ($lx_option['level'] ?? null) {
				$ls_title = str_repeat($attributes['levelPrefix'] ?? '- ', $lx_option['level']) . $ls_title;
			}

			$la_options[ $lx_option[ $la_combinator[0] ] ] = $ls_title;
		}

		return $la_options;
	}


	/**
	 * @param \Cake\Collection\Iterator\TreeIterator $options
	 * @param array $attributes
	 * @param bool $forLinkSelect
	 * @return array
	 */
	protected function formatTreeOptions(TreeIterator $options, array $attributes, bool $forLinkSelect): array {
		if ($forLinkSelect) {
			$la_options = $options->toList();
			$la_options = array_combine(array_column($la_options, 'id'), $la_options);
		}
		else {
			$la_options = $options->printer(...($attributes['printer'] ?? ['label', 'id', $attributes['levelPrefix'] ?? '- ']))->toArray();
		}

		return $la_options;
	}


	/**
	 * @param array $options
	 * @param array $attributes
	 * @return array
	 */
	protected function formatOptionAttributes(array $options, array $attributes): array {
		$la_formattedOptions = [];

		foreach ($options as $lx_key => $lx_option) {
			if (is_object($lx_option)) {
				$la_data = [
					'id' => $lx_option->id,
					'title' => $lx_option->label ?? $lx_option->title,
					'link' => $this->Url->build([
						'_name' => $attributes['routeName'] ?? null,
						$attributes['uriParam'] => $lx_option->id,
					], ['withoutParams' => ['page']]),
					'levelPrefix' => str_repeat($attributes['levelPrefix'] ?? '', $lx_option->level ?? 0),
					'isGrouped' => $attributes['isGrouped'] ?? false,
				];
				$la_formattedOptions[ $lx_option->id ] = $la_data;
			}
			elseif (is_array($lx_option)) {
				$la_formattedOptions[ $lx_option['id'] ?? $lx_key ] = $lx_option + ['isGrouped' => $attributes['isGrouped'] ?? false];
			}
			else {
				$la_data = [
					'id' => null,
					'title' => $lx_option,
					'link' => $this->Url->build([
						'_name' => $attributes['routeName'] ?? null,
						$attributes['uriParam'] => $lx_key,
					], ['withoutParams' => ['page']]),
					'levelPrefix' => null,
					'isGrouped' => $attributes['isGrouped'] ?? false,
				];

				$la_formattedOptions[ $lx_key ] = $la_data;
			}
		}


		return $la_formattedOptions;
	}


	/**
	 * @param mixed $options
	 * @param mixed $groupBy
	 * @param array $attributes
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function groupOptions(CollectionInterface $options, string $groupBy, array &$attributes): array {
		return $options->groupBy(function (mixed $element) use ($groupBy, &$attributes) {
			$lx_group = $element->$groupBy ?? $element[ $groupBy ] ?? null;

			if (is_array($lx_group)) {
				$ls_path = implode(' - ', array_map(function (mixed $parent) {
					if (is_scalar($parent)) {
						return $parent;
					}

					if (!$parent instanceof EntityInterface) {
						throw new RuntimeException('Cannot group by non-scalars or non-entities.');
					}

					/** @noinspection PhpPossiblePolymorphicInvocationInspection */
					return $parent->label ?? $parent->title;
				}, $lx_group));

				/** @noinspection PhpVariableNamingConventionInspection */
				$attributes['groupLabels'][ $ls_path ] ??= $ls_path;

				return $ls_path;
			}

			return $element[ $groupBy ] ?? '';
		})->toArray();
	}
}
