<?php declare(strict_types=1);


namespace Awyiss\Widget;


use Awyiss\Model\Entity\Language;
use Awyiss\View\BackendView;


/**
 * Class RoutePlannerWidget
 * Show a map using OpenStreetMap and MapLibre,
 * as well as a route planner form.
 */
class RoutePlannerWidget extends AbstractWidget {
	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		// Translate using __d() if needed
		return 'Route Planner';
	}


	/**
	 * @inheritDoc
	 */
	public static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
		return [
			'settings.address' => [
				'label' => __d('Frontend/route', 'address'),
				'value' => $settings['address'] ?? null,
				'data-geocode' => 'true',
				'data-geocode-settings' => json_encode([
					'lat' => 'input[name="settings[lat]"]',
					'lng' => 'input[name="settings[lng]"]',
					'buttonLabel' => __d('Frontend/route', 'geocode_address'),
				]),
			],

			'settings.lat' => [
				'columnSpan' => 6,
				'label' => __d('Frontend/route', 'lat'),
				'max' => 90,
				'min' => -90,
				'required' => true,
				'step' => 0.000001,
				'type' => 'number',
				'value' => $settings['lat'] ?? null,
			],

			'settings.lng' => [
				'columnSpan' => 6,
				'label' => __d('Frontend/route', 'lng'),
				'max' => 180,
				'min' => -180,
				'required' => true,
				'step' => 0.000001,
				'type' => 'number',
				'value' => $settings['lng'] ?? null,
			],

			'settings.transportationMode' => [
				'columnSpan' => 6,
				'label' => __d('Frontend/route', 'transportation_mode'),
				'options' => [
					'car' => __d('Frontend/route', 'transportation_mode_car'),
					'bike' => __d('Frontend/route', 'transportation_mode_bike'),
					'foot' => __d('Frontend/route', 'transportation_mode_foot'),
				],
				'type' => 'radio',
				'val' => $settings['transportationMode'] ?? false,
			],

			'settings.showTransportationModes' => [
				'checked' => $settings['showTransportationModes'] ?? false,
				'columnSpan' => 6,
				'label' => __d('Frontend/route', 'show_transportation_modes'),
				'type' => 'checkbox',
			],
		];
	}
}
