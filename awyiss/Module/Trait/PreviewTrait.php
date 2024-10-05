<?php declare(strict_types=1);


namespace Awyiss\Module\Trait;


use Awyiss\Routing\Router;


/**
 * Trait PreviewTrait
 * Provides a method to check if the current request is in preview mode
 */
trait PreviewTrait {
	/**
	 * Check if the current request is in preview mode
	 *
	 * @return bool
	 */
	protected static function isPreview(): bool {
		static $lb_isPreview = null;

		if (isset($lb_isPreview)) {
			return $lb_isPreview;
		}

		$lb_isPreview = !!(Router::getRequest()?->getSession()->read('previewMode.enabled', false));

		return $lb_isPreview;
	}
}
