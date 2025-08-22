<?php declare(strict_types=1);


namespace Awyiss\Module\Trait;


use Awyiss\Routing\Router;


/**
 * Trait PreviewTrait
 * Provides a method to check if the current request is in preview mode
 */
trait PreviewTrait {
	/**
	 * @var bool|null
	 */
	protected static ?bool $isPreview = null;


	/**
	 * Check if the current request is in preview mode
	 *
	 * @return bool
	 */
	protected static function isPreview(): bool {
		if (isset(static::$isPreview)) {
			return static::$isPreview;
		}

		static::$isPreview = !!(Router::getRequest()?->getSession()->read('previewMode.enabled', false));

		return static::$isPreview;
	}
}
