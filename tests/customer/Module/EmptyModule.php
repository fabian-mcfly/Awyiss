<?php declare(strict_types=1);


namespace Customer\Module;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Module\ModuleInterface;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;


/**
 * Class EmptyModule
 */
class EmptyModule implements ModuleInterface {
	/**
	 * The identifier of the module
	 *
	 * @var string
	 */
	protected static string $identifier = 'emptyModule';


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
		return 'Empty Module';
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
