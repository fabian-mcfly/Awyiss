<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Cake\Cache\Cache;
use RuntimeException;


/**
 * Basic webfont provider for Google fonts using
 * google-webfonts-helper by Mario Ranftl
 *
 * @see https://gwfh.mranftl.com
 */
class WebfontProvider {
	/**
	 * @var string $fontApiUrl
	 */
	protected string $fontApiUrl = 'https://gwfh.mranftl.com/api/fonts';
	/**
	 * @var array $webfonts
	 */
	protected array $webfonts = [];


	/**
	 * @param array $webfonts
	 */
	public function __construct() {
		$this->webfonts = Cache::remember('webfonts', fn() => $this->fetchWebfonts(), 'persistent');
	}


	/**
	 * Fetches the webfonts from the google-webfonts-helper
	 * and stores them in the $webfonts property
	 *
	 * @return array<string, array{category: string, id: string, name: string, popularity: int, variants: array, version: string}>
	 */
	protected function fetchWebfonts(): array {
		$la_apiResult = json_decode(file_get_contents($this->fontApiUrl), true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('Could not fetch webfonts');
		}

		$la_webfonts = [];
		foreach ($la_apiResult as $la_font) {
			if (!isset($la_font['subsets']) || !in_array('latin', $la_font['subsets'], true)) {
				continue;
			}

			$la_webfonts[ $la_font['id'] ] = [
				'category' => $la_font['category'],
				'id' => $la_font['id'],
				'name' => $la_font['family'],
				'popularity' => $la_font['popularity'],
				'variants' => $la_font['variants'],
				'version' => $la_font['version'],
			];
		}

		uasort($la_webfonts, function ($a, $b) {
			return strnatcasecmp($a['name'], $b['name']);
		});

		Cache::write('webfonts', $la_webfonts);

		return $la_webfonts;
	}


	/**
	 * @return array
	 */
	public function getWebfonts(): array {
		return $this->webfonts;
	}
}
