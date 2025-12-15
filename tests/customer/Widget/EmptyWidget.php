<?php declare(strict_types=1);


namespace Customer\Widget;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Awyiss\Widget\WidgetInterface;


/**
 * Class EmptyWidget
 */
class EmptyWidget implements WidgetInterface {
	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		return 'Empty Widget';
	}


	/**
	 * @inheritDoc
	 */
	public static function isAvailable(): bool {
		return true;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function renderForm(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): string {
		return '';
	}


	/**
	 * @inheritDoc
	 */
	public static function render(array $settings, FrontendView $view, ?MediaRenderOptions $mediaRenderOptions, ?Entity $entity = null, ?Language $frontendLanguage = null): string {
		return '';
	}
}
