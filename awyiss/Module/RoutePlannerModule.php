<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;


/**
 * Class RoutePlannerModule
 * Show a map using OpenStreetMap and MapLibre,
 * as well as a route planner form.
 */
class RoutePlannerModule implements ModuleInterface {
	/**
	 * The identifier of the module
	 *
	 * @var string
	 */
	protected static string $identifier = 'routePlanner';


	/**
	 * @inheritDoc
	 */
	public static function getIdentifier(): string {
		return static::$identifier;
	}


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		// Translate using __d() if needed
		return 'Route Planner';
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function renderForm(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): string {
		$ls_return = '';

		/**
		 * Get the form helper
		 *
		 * @var \Awyiss\View\Helper\FormHelper $lo_formHelper
		 */
		$lo_formHelper = $view->helpers()->get('Form');

		$ls_return .= $lo_formHelper->control('settings.address', [
			'label' => __d('Frontend/route', 'address'),
			'value' => $settings['address'] ?? null,
			'data-geocode' => 'true',
			'data-geocode-settings' => json_encode([
				'lat' => 'input[name="settings[lat]"]',
				'lng' => 'input[name="settings[lng]"]',
				'buttonLabel' => __d('Frontend/route', 'geocode_address'),
			]),
		]);

		$ls_return .= $lo_formHelper->control('settings.lat', [
			'columnSpan' => 6,
			'label' => __d('Frontend/route', 'lat'),
			'max' => 90,
			'min' => -90,
			'required' => true,
			'step' => 0.000001,
			'type' => 'number',
			'value' => $settings['lat'] ?? null,
		]);

		$ls_return .= $lo_formHelper->control('settings.lng', [
			'columnSpan' => 6,
			'label' => __d('Frontend/route', 'lng'),
			'max' => 180,
			'min' => -180,
			'required' => true,
			'step' => 0.000001,
			'type' => 'number',
			'value' => $settings['lng'] ?? null,
		]);

		$ls_return .= $lo_formHelper->control('settings.transportationMode', [
			'columnSpan' => 6,
			'label' => __d('Frontend/route', 'transportation_mode'),
			'options' => [
				'car' => __d('Frontend/route', 'transportation_mode_car'),
				'bike' => __d('Frontend/route', 'transportation_mode_bike'),
				'foot' => __d('Frontend/route', 'transportation_mode_foot'),
			],
			'type' => 'radio',
			'val' => $settings['transportationMode'] ?? false,
		]);

		$ls_return .= $lo_formHelper->control('settings.showTransportationModes', [
			'checked' => $settings['showTransportationModes'] ?? false,
			'columnSpan' => 6,
			'label' => __d('Frontend/route', 'show_transportation_modes'),
			'type' => 'checkbox',
		]);

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
		return $view->element('module/route_planner', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'settings' => $settings,
		]);
	}
}
