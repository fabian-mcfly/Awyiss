<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Awyiss\Utility\Arrays;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
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
	 * @var array<string, array{category: string, id: string, name: string, popularity: int, variants: array, version: string}> $webfonts
	 */
	protected array $webfonts = [];


	/**
	 * Fetches the webfonts from the google-webfonts-helper
	 * and stores them in the $webfonts property
	 *
	 * @return array<string, array{category: string, id: string, name: string, popularity: int, variants: array, version: string}>
	 */
	protected function fetchWebfonts(): array {
		$apiResult = json_decode(file_get_contents($this->fontApiUrl), true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('Could not fetch webfonts');
		}

		$webfonts = [];
		foreach ($apiResult as $font) {
			if (!isset($font['subsets']) || !in_array('latin', $font['subsets'], true)) {
				continue;
			}

			$webfonts[ $font['id'] ] = [
				'category' => $font['category'],
				'id' => $font['id'],
				'name' => $font['family'],
				'popularity' => $font['popularity'],
				'variants' => $font['variants'],
				'version' => $font['version'],
			];
		}

		Arrays::naturalSort($webfonts, 'name');

		return $webfonts;
	}


	/**
	 * Clears the cache of the webfonts
	 *
	 * @return $this
	 */
	public function clearCache(): static {
		Cache::delete('webfonts', 'persistent');

		return $this;
	}


	/**
	 * @return array
	 */
	public function getWebfonts(): array {
		$this->webfonts = Cache::remember('webfonts', fn() => $this->fetchWebfonts(), 'persistent');

		$webfonts = new Collection($this->webfonts);

		return $webfonts->filter(fn($font) => $font['popularity'] < 1000)->toArray();
	}
}
