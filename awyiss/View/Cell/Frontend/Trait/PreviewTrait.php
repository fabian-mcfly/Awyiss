<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend\Trait;


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
	protected function isPreview(): bool {
		static $isPreview = null;

		if (isset($isPreview)) {
			return $isPreview;
		}

		$isPreview = !!(Router::getRequest()?->getSession()->read('previewMode.enabled', false));

		return $isPreview;
	}
}
