<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\Core\Exception\CakeException;
use Cake\Datasource\EntityInterface;
use Cake\View\StringTemplate;
use Cake\View\StringTemplateTrait;


/**
 * @property \Awyiss\View\Helper\FormHelper $Form
 */
class SystemOrderHelper extends \Cake\View\Helper {
	use StringTemplateTrait;


	protected $_defaultConfig = [
		'after' => NULL,
		'first' => NULL,
		'includeFirst' => TRUE,
		'templateClass' => \Awyiss\View\StringTemplate::class,
		'templates' => [
			'titleOption' => '{{after}} {{title}}',
			'titleOptionCurrent' => '{{title}}',
			'titleOptionSelected' => '-> {{after}} {{title}}',
			'titleFirst' => '{{first}}',
			'titleFirstCurrent' => '{{first}}',
			'titleFirstSelected' => '-> {{first}}',
		],
	];
	public $helpers = ['Form'];


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->_templater = new StringTemplate();
	}


	public function control (?string $as_fieldName = NULL, array $aa_attributes = []): string {
		$la_cachedConfig = $this->getConfig();

		$ls_fieldName = $as_fieldName ?? 'system_order';
		$this->setConfig($aa_attributes);
		$la_attributes = $this->getConfig();

		$lo_entity = $la_attributes['entity'] ?? NULL;
		if ( ! $lo_entity) {
			throw new CakeException('Missing entity for SystemOrderHelper::control');
		}
		if ( ! ($lo_entity instanceof EntityInterface)) {
			$ls_type = is_object($lo_entity) ? get_class($lo_entity) : gettype($lo_entity);
			$ls_message = sprintf('Entity provided must be an instance of `%s`, `%s` given.', EntityInterface::class, $ls_type);

			throw new \RuntimeException($ls_message);
		}

		if (empty($la_attributes['options'])) {
			$la_attributes['options'] = $this->getView()->get('ao_systemOrderRecords') ?? [];
		}

		if (empty($la_attributes['relatedColumns'])) {
			$la_attributes['relatedColumns'] = $this->getView()->get('aa_systemOrderRelatedColumns') ?? [];
		}

		if ( ! is_array($la_attributes['options'])) {
			$la_attributes['options'] = $this->buildSystemOrderOptions($la_attributes['options'], $la_attributes, $lo_entity);
		}

		if ( ! array_key_exists('type', $la_attributes)) {
			$la_attributes['type'] = 'select';
		}

		unset($la_attributes['entity'], $la_attributes['includeFirst'], $la_attributes['relatedColumns'], $la_attributes['templateClass'], $la_attributes['templates'], $la_attributes['titleFirst']);

		$this->setConfig($la_cachedConfig, NULL, FALSE);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->Form->control($ls_fieldName, $la_attributes + [
				'val' => $lo_entity->system_order,
				'disabled' => [\Awyiss\Model\Behavior\SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER],
			]);
	}


	protected function buildSystemOrderOptions (iterable $ax_options, array $aa_attributes, EntityInterface $ao_entity): array {
		$la_options = [];
		$lb_isNew = $ao_entity->isNew();
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $aa_attributes['relatedColumns']);

		if ($aa_attributes['includeFirst']) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_options[1] = $this->formatFirst( $aa_attributes + [
					'isCurrentSystemOrder' => ! $lb_isNew && 1 === $ao_entity->getOriginal('system_order'),
					'isSelectedSystemOrder' => 1 === $ao_entity->system_order,
				]);
		}

		$lb_reachedCurrent = FALSE;
		foreach ($ax_options as $lx_option) {
			$lb_isCurrentSystemOrder = ! $lb_isNew && ! $la_dirtyRelatedColumns && $lx_option->system_order == $ao_entity->getOriginal('system_order');

			if ($lb_isCurrentSystemOrder && ! $lb_reachedCurrent) {
				$lb_reachedCurrent = TRUE;
			}

			$li_systemOrder = $lx_option->system_order;
			if ( ! $lb_reachedCurrent) {
				$li_systemOrder++;
			}

			if ($lb_isCurrentSystemOrder) {
				$li_systemOrder = \Awyiss\Model\Behavior\SystemOrderBehavior::CURRENT_VALUE_PLACEHOLDER;
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$ls_title = is_string($lx_option)
				? $lx_option
				: $this->formatTitle($lx_option, $aa_attributes + [
						'isCurrentSystemOrder' => $lb_isCurrentSystemOrder,
						'isSelectedSystemOrder' => $li_systemOrder == $ao_entity->system_order,
					]);

			$la_options[ $li_systemOrder ] = $ls_title;
		}

		return $la_options;
	}


	protected function formatFirst (array $aa_config): string {
		$ls_template = 'titleFirst';
		$la_config = $aa_config;

		if ($la_config['isCurrentSystemOrder']) {
			$ls_template = 'titleFirstCurrent';
		}

		if ($la_config['isSelectedSystemOrder']) {
			$ls_template = 'titleFirstSelected';
		}

		$lx_formatter = $la_config['templates'][ $ls_template ];

		if (is_string($lx_formatter)) {
			$this->templater()->add([$ls_template => $lx_formatter]);
			$ls_title = $this->formatTemplate($ls_template, [
				'first' => is_null($la_config['first']) ? __('::system_order_first') : $la_config['first'],
			] + $la_config);
		}
		elseif (is_callable($lx_formatter)) {
			$ls_title = call_user_func($lx_formatter, $la_config);
		}

		return $ls_title ?? '';
	}


	protected function formatTitle (mixed $ax_option, array $aa_config): string {
		$ls_title = '';
		$ls_template = 'titleOption';
		$la_config = $aa_config;

		if ($la_config['isCurrentSystemOrder']) {
			$ls_template = 'titleOptionCurrent';
		}

		if ($la_config['isSelectedSystemOrder']) {
			$ls_template = 'titleOptionSelected';
		}

		$lx_formatter = $la_config['templates'][ $ls_template ];

		if (is_string($lx_formatter)) {
			if (is_array($ax_option)) {
				$la_option = $ax_option;
			}
			elseif (is_a($ax_option, EntityInterface::class)) {
				$la_option = $ax_option->toArray();
			}

			$this->templater()->add([$ls_template => $lx_formatter]);
			$ls_title = $this->formatTemplate($ls_template, ($la_option ?? []) + [
				'after' => is_null($la_config['after']) ? __('::system_order_after') : $la_config['after'],
			]);
		}
		elseif (is_callable($lx_formatter)) {
			$ls_title = call_user_func($lx_formatter, $ax_option, $la_config);
		}

		return $ls_title;
	}
}