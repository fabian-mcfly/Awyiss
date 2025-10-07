<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;


/**
 * Class AbstractModule
 * Abstract class for all modules
 */
abstract class AbstractModule implements ModuleInterface {
	use Trait\PreviewTrait;


	/**
	 * Get the form fields for the module.
	 *
	 * @return array<string, string|array<string, mixed>>
	 */
	abstract protected static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array;


	/**
	 * @inheritDoc
	 */
	public static function isAvailable(): bool {
		// Check if the module is available in the current context
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
		 * @var \Awyiss\View\Helper\FormHelper $lo_formHelper
		 */
		$lo_formHelper = $view->helpers()->get('Form');

		$ls_return = '';

		foreach (static::getFormFields($view, $frontendLanguage, $userLanguage, $settings) as $ls_name => $lx_options) {
			if (is_string($lx_options)) {
				$ls_return .= $lx_options;
				continue;
			}

			$ls_methodName = $lx_options['method'] ?? 'control';
			unset($lx_options['method']);

			$ls_return .= $lo_formHelper->{$ls_methodName}($ls_name, $lx_options);
		}

		return $ls_return;
	}


	/**
	 * @inheritDoc
	 */
	public static function render(
		array $settings,
		FrontendView $view,
		?MediaRenderOptions $mediaRenderOptions,
		?Entity $entity = null,
		?Language $frontendLanguage = null
	): string {
		$ls_elementName = Inflector::underscore(ModulesProvider::extractIdentifierFromClassName(static::class));

		return $view->element('module/' . $ls_elementName, [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'settings' => $settings,
		]);
	}
}
