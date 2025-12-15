<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Widget;


use Awyiss\Model\Entity\Language;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Awyiss\Widget\RoutePlannerWidget;


/**
 * Test case for RoutePlannerWidget
 *
 * @see \Awyiss\Widget\RoutePlannerWidget
 */
class RoutePlannerWidgetTest extends TestCase {
	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $mockBackendView;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $mockFrontendView;
	/**
	 * @var \Awyiss\Model\Entity\Language
	 */
	protected Language $mockLanguage;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockBackendView = $this->createMock(BackendView::class);
		$this->mockFrontendView = $this->createMock(FrontendView::class);
		$this->mockLanguage = $this->createMock(Language::class);
	}


	/**
	 * Test getTitle method returns 'Route Planner'
	 *
	 * @return void
	 * @see \Awyiss\Widget\RoutePlannerWidget::getTitle()
	 */
	public function testGetTitle(): void {
		$result = RoutePlannerWidget::getTitle();

		$this->assertSame('Route Planner', $result);
	}


	/**
	 * Test isAvailable method returns true
	 *
	 * @return void
	 * @see \Awyiss\Widget\RoutePlannerWidget::isAvailable()
	 */
	public function testIsAvailable(): void {
		$result = RoutePlannerWidget::isAvailable();

		$this->assertTrue($result);
	}


	/**
	 * Test getFormFields method with default settings
	 *
	 * @return void
	 * @see \Awyiss\Widget\RoutePlannerWidget::getFormFields()
	 */
	public function testGetFormFieldsWithDefaults(): void {
		$result = RoutePlannerWidget::getFormFields($this->mockBackendView);

		$this->assertIsArray($result);

		// Test that all required form fields are present
		$this->assertArrayHasKey('settings.address', $result);
		$this->assertArrayHasKey('settings.lat', $result);
		$this->assertArrayHasKey('settings.lng', $result);
		$this->assertArrayHasKey('settings.transportationMode', $result);
		$this->assertArrayHasKey('settings.showTransportationModes', $result);

		// Test address field properties
		$addressField = $result['settings.address'];
		$this->assertNull($addressField['value']);
		$this->assertSame('true', $addressField['data-geocode']);
		$this->assertIsString($addressField['data-geocode-settings']);

		// Test that geocode settings is valid JSON
		$geocodeSettings = json_decode($addressField['data-geocode-settings'], true);
		$this->assertIsArray($geocodeSettings);
		$this->assertArrayHasKey('lat', $geocodeSettings);
		$this->assertArrayHasKey('lng', $geocodeSettings);
		$this->assertArrayHasKey('buttonLabel', $geocodeSettings);
		$this->assertSame('input[name="settings[lat]"]', $geocodeSettings['lat']);
		$this->assertSame('input[name="settings[lng]"]', $geocodeSettings['lng']);

		// Test latitude field properties
		$latField = $result['settings.lat'];
		$this->assertSame(6, $latField['columnSpan']);
		$this->assertSame(90, $latField['max']);
		$this->assertSame(-90, $latField['min']);
		$this->assertTrue($latField['required']);
		$this->assertSame(0.000001, $latField['step']);
		$this->assertSame('number', $latField['type']);
		$this->assertNull($latField['value']);

		// Test longitude field properties
		$lngField = $result['settings.lng'];
		$this->assertSame(6, $lngField['columnSpan']);
		$this->assertSame(180, $lngField['max']);
		$this->assertSame(-180, $lngField['min']);
		$this->assertTrue($lngField['required']);
		$this->assertSame(0.000001, $lngField['step']);
		$this->assertSame('number', $lngField['type']);
		$this->assertNull($lngField['value']);

		// Test transportation mode field properties
		$transportationModeField = $result['settings.transportationMode'];
		$this->assertSame(6, $transportationModeField['columnSpan']);
		$this->assertSame('radio', $transportationModeField['type']);
		$this->assertFalse($transportationModeField['val']);
		$this->assertIsArray($transportationModeField['options']);
		$this->assertArrayHasKey('car', $transportationModeField['options']);
		$this->assertArrayHasKey('bike', $transportationModeField['options']);
		$this->assertArrayHasKey('foot', $transportationModeField['options']);

		// Test show transportation modes checkbox
		$showTransportationModesField = $result['settings.showTransportationModes'];
		$this->assertFalse($showTransportationModesField['checked']);
		$this->assertSame(6, $showTransportationModesField['columnSpan']);
		$this->assertSame('checkbox', $showTransportationModesField['type']);
	}


	/**
	 * Test getFormFields method with custom settings
	 *
	 * @return void
	 * @see \Awyiss\Widget\RoutePlannerWidget::getFormFields()
	 */
	public function testGetFormFieldsWithCustomSettings(): void {
		$settings = [
			'address' => '123 Main Street, City',
			'lat' => 52.5200,
			'lng' => 13.4050,
			'transportationMode' => 'bike',
			'showTransportationModes' => true,
		];

		$result = RoutePlannerWidget::getFormFields($this->mockBackendView, null, null, $settings);

		// Test that custom values are properly applied
		$this->assertSame('123 Main Street, City', $result['settings.address']['value']);
		$this->assertSame(52.5200, $result['settings.lat']['value']);
		$this->assertSame(13.4050, $result['settings.lng']['value']);
		$this->assertSame('bike', $result['settings.transportationMode']['val']);
		$this->assertTrue($result['settings.showTransportationModes']['checked']);
	}


	/**
	 * Test getFormFields method with different transportation modes
	 *
	 * @dataProvider transportationModeDataProvider
	 * @param string $mode Transportation mode to test
	 * @return void
	 * @see \Awyiss\Widget\RoutePlannerWidget::getFormFields()
	 */
	public function testGetFormFieldsWithDifferentTransportationModes(string $mode): void {
		$settings = ['transportationMode' => $mode];

		$result = RoutePlannerWidget::getFormFields($this->mockBackendView, null, null, $settings);

		$this->assertSame($mode, $result['settings.transportationMode']['val']);
		$this->assertArrayHasKey($mode, $result['settings.transportationMode']['options']);
	}


	/**
	 * Data provider for transportation modes
	 *
	 * @return array<string, array{string}>
	 */
	public static function transportationModeDataProvider(): array {
		return [
			'car mode' => ['car'],
			'bike mode' => ['bike'],
			'foot mode' => ['foot'],
		];
	}
}
