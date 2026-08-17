<?php declare(strict_types=1);


namespace Awyiss\Widget;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;


/**
 * Signature of all necessary methods to connect `Widget` with `WidgetsProvider`
 */
interface WidgetInterface {
	/**
	 * Returns the human-readable, translated title of the widget
	 *
	 * @return string
	 */
	public static function getTitle(): string;


	/**
	 * Returns whether the widget is available.
	 * Not all widgets are available in all contexts.
	 *
	 * @return bool
	 */
	public static function isAvailable(): bool;


	/**
	 * Renders the form for the widget configuration
	 * All used form elements should be translated:
	 *  - the values to the frontend language
	 *  - the labels to the user language
	 *
	 * All form elements should be under the key `settings`
	 *
	 * @param \Awyiss\View\BackendView $view
	 * @param \Awyiss\Model\Entity\Language|null $frontendLanguage
	 * @param \Awyiss\Model\Entity\Language|null $userLanguage
	 * @param array $settings
	 * @return string
	 */
	public static function renderForm(
		BackendView $view,
		?Language $frontendLanguage = null,
		?Language $userLanguage = null,
		array $settings = []
	): string;


	/**
	 * Renders the widget
	 *
	 * When called from within a cell, call ob_end_clean() before any `dump()` or `dd()` calls
	 * to prevent the output from being buffered, otherwise the output will be empty.
	 *
	 * @param array $settings
	 * @param \Awyiss\View\FrontendView $view
	 * @param \Awyiss\Utility\Media\MediaRenderOptions|null $mediaRenderOptions
	 * @param \Awyiss\Model\Entity|null $entity
	 * @param \Awyiss\Model\Entity\Language|null $frontendLanguage
	 * @return string
	 */
	public static function render(
		array $settings,
		FrontendView $view,
		?MediaRenderOptions $mediaRenderOptions = null,
		?Entity $entity = null,
		?Language $frontendLanguage = null
	): string;
}
