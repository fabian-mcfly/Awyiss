<?php declare(strict_types=1);


namespace Awyiss\Widget;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;


/**
 * Class AbstractWidget
 * Abstract class for all widgets
 */
abstract class AbstractWidget implements WidgetInterface {
	use Trait\PreviewTrait;


	/**
	 * Get the form fields for the widget.
	 *
	 * @param \Awyiss\View\BackendView $view The backend view instance.
	 * @param \Awyiss\Model\Entity\Language|null $frontendLanguage The frontend language entity, if available.
	 * @param \Awyiss\Model\Entity\Language|null $userLanguage The user language entity, if available.
	 * @param array $settings Additional settings for the widget form fields.
	 * @return array<string, string|array<string, mixed>>
	 */
	abstract protected static function getFormFields(
		BackendView $view,
		?Language $frontendLanguage = null,
		?Language $userLanguage = null,
		array $settings = []
	): array;


	/**
	 * @inheritDoc
	 */
	public static function isAvailable(): bool {
		// Check if the widget is available in the current context
		// This can be overridden in the child class
		return true;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function renderForm(
		BackendView $view,
		?Language $frontendLanguage = null,
		?Language $userLanguage = null,
		array $settings = []
	): string {
		/**
		 * Get the form helper
		 *
		 * @var \Awyiss\View\Helper\FormHelper $formHelper
		 */
		$formHelper = $view->helpers()->get('Form');

		$return = '';

		foreach (static::getFormFields($view, $frontendLanguage, $userLanguage, $settings) as $name => $options) {
			if (is_string($options)) {
				$return .= $options;
				continue;
			}

			$methodName = $options['method'] ?? 'control';
			unset($options['method']);

			$return .= $formHelper->{$methodName}($name, $options);
		}

		return $return;
	}


	/**
	 * @inheritDoc
	 */
	public static function render(
		array $settings,
		FrontendView $view,
		?MediaRenderOptions $mediaRenderOptions = null,
		?Entity $entity = null,
		?Language $frontendLanguage = null
	): string {
		$elementName = Inflector::underscore(WidgetsProvider::extractIdentifierFromClassName(static::class));

		return $view->element('widget/' . $elementName, [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'settings' => $settings,
		]);
	}
}
