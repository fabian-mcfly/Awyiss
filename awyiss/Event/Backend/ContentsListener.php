<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\Content;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use ScssPhp\ScssPhp\Exception\SassException;


/**
 * Event listeners for the Contents scope of the backend
 */
class ContentsListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Contents.beforeSave' => 'beforeSave',
			'Configuration.Contents.Backend.columnSystem.className.afterSaveCommit' => 'recompileAfterClassNameSave',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterSaveCommit' => 'recompileAfterMaxColumnsSave',
			'Configuration.Contents.Backend.columnSystem.className.afterDeleteCommit' => 'recompileAfterClassNameDelete',
			'Configuration.Contents.Backend.columnSystem.maxColumns.afterDeleteCommit' => 'recompileAfterMaxColumnsDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Content $entity): void {
		// Unset titleTag and subtitleTag if title and subtitle are empty
		if (!$entity->title && $entity->titleTag) {
			$entity->titleTag = null;
		}

		if (!$entity->subtitle && $entity->subtitleTag) {
			$entity->subtitleTag = null;
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function recompileAfterClassNameSave(Event $event, Configuration $configuration): void {
		$this->recompileScss('className', $configuration->value);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function recompileAfterMaxColumnsSave(Event $event, Configuration $configuration): void {
		$this->recompileScss('maxColumns', (int)$configuration->value);
	}


	/**
	 * @return void
	 */
	public function recompileAfterClassNameDelete(): void {
		$this->recompileScss('className', false);
	}


	/**
	 * @return void
	 */
	public function recompileAfterMaxColumnsDelete(): void {
		$this->recompileScss('maxColumns', false);
	}


	/**
	 * If the class name or the max columns of the column system is changed,
	 * we need to recompile the SCSS files to apply the changes.
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return void
	 */
	protected function recompileScss(string $type, mixed $value): void {
		if ($value !== false) {
			Configure::write('Awyiss.Contents.Backend.columnSystem.' . $type, $value);
		}
		else {
			Configure::delete('Awyiss.Contents.Backend.columnSystem.' . $type);
		}

		try {
			/** @var \Awyiss\Middleware\DesignMiddleware $lo_designMiddleware */
			$lo_designMiddleware = Router::getRequest()->getAttribute('design');
			$lo_designMiddleware->resetDesignVariables();
			$lo_designMiddleware->compileScss(true, Awyiss::REALM_FRONTEND);

			$lo_designMiddleware->resetDesignVariables();
			$lo_designMiddleware->compileScss(true, Awyiss::REALM_BACKEND);
		}
		catch (SassException) {
			// Ignore SCSS compilation errors here
		}
	}
}
