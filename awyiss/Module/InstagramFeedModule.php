<?php declare(strict_types=1);


namespace Awyiss\Module;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\Utility\Media\ResizedImageManager;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Instagram\Api;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Throwable;


/**
 * Class NewsListingModule
 * Show a list of news, either paginated or limited to a certain number of items
 */
class InstagramFeedModule implements ModuleInterface {
	/**
	 * The identifier of the module
	 *
	 * @var string
	 */
	protected static string $identifier = 'instagramFeed';


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
		// Translate using __d() if needed
		return 'Intagram Feed';
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public static function renderForm(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): string {
		$ls_return = '';

		/**
		 * Get the form helper
		 *
		 * @var \Awyiss\View\Helper\FormHelper $lo_formHelper
		 */
		$lo_formHelper = $view->helpers()->get('Form');

		// A dropdown to select the homepage (for the current language)
		$ls_return .= $lo_formHelper->control('settings.items', [
			'columnSpan' => 6,
			'label' => __d('module', 'instagram_items'),
			'placeholder' => '6',
			'type' => 'number',
			'value' => $settings['items'] ?? null,
		]);

		// A dropdown to select the homepage (for the current language)
		$ls_return .= $lo_formHelper->control('settings.profileName', [
			'columnSpan' => 6,
			'label' => __d('module', 'instagram_profile_name'),
			'placeholder' => Configure::read('Instagram.userName'),
			'value' => $settings['profileName'] ?? null,
		]);

		return $ls_return;
	}


	/**
	 * @inheritDoc
	 */
	public static function render(
		array $settings,
		FrontendView $view,
		?MediaRenderOptions $mediaRenderOptions,
		?Entity $entity = null,
		?Language $frontendLanguage = null
	): string {
		$lb_credentialsSet = Configure::check('Instagram.userName') && Configure::check('Instagram.password');

		$ls_userName = Configure::read('Instagram.userName');
		$li_items = $settings['items'] ?? 6;
		$li_cacheLifetime = 60 * 60 * 24 * 365;
		$la_media = [];
		$ls_profileName = $settings['profileName'] ?? $ls_userName;

		$ls_errorMessage = null;
		if ($lb_credentialsSet) {
			try {
				$la_media = static::fetchMedia($ls_userName, $ls_profileName, $li_items, $li_cacheLifetime);
			}
			catch (Throwable $ex) {
				$ls_errorMessage = $ex->getMessage();
			}
		}

		if ($la_media) {
			$la_media = array_slice($la_media, 0, $li_items);

			$la_mediaIds = array_map(function ($lo_media) {
				return $lo_media->getId();
			}, $la_media);

			/** @var \Awyiss\Model\Table\MediaTable $lo_mediaTable */
			$lo_mediaTable = FactoryLocator::get('Table')->get('Media');
			$la_mediaEntities = $lo_mediaTable->find('all')
				->where(['id IN' => $la_mediaIds])
				->contain(['MediaResizedImages'])
				->all()
				->indexBy('id')
				->toArray();

			/** @var \Instagram\Model\Media $lo_media */
			foreach ($la_media as $lo_media) {
				$lo_media->mediaEntity = $la_mediaEntities[ $lo_media->getId() ] ?? null;
			}

			// Set the media items for the ResizedImageManager
			ResizedImageManager::setMediaItems($la_mediaEntities);
		}

		return $view->element('module/instagram_feed', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'credentialsSet' => $lb_credentialsSet,
			'errorMessage' => $ls_errorMessage,
			'items' => $li_items,
			'media' => $la_media,
			'profileName' => $ls_profileName,
			'settings' => $settings,
		]);
	}


	/**
	 * @param string $userName
	 * @param string $profileName
	 * @param int $items
	 * @param int $cacheLifetime
	 * @return array<\Instagram\Model\Media>
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \Instagram\Exception\InstagramAuthException
	 * @throws \Instagram\Exception\InstagramException
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	protected static function fetchMedia(string $userName, string $profileName, int $items, int $cacheLifetime): array {
		// Try loading the medias from the cache
		$la_media = Cache::read('instagram_profile_' . $profileName, 'instagram');

		if (!empty($la_media)) {
			return $la_media;
		}

		$lo_cachePool = new FilesystemAdapter('Instagram', $cacheLifetime, TMP . 'instagram_profile');

		$lo_api = new Api($lo_cachePool);
		$lo_api->login($userName, Configure::read('Instagram.password'));
		$lo_profile = $lo_api->getProfile($profileName);

		$la_media = $lo_profile->getMedias();
		$li_mediaCount = count($la_media);

		while ($li_mediaCount < $items) {
			$lo_profile = $lo_api->getMoreMedias($lo_profile);
			$la_newMedia = $lo_profile->getMedias();

			if (empty($la_newMedia)) {
				break;
			}

			$la_media = array_merge($la_media, $la_newMedia);
			$li_mediaCount = count($la_media);

			// avoid 429 Rate limit from Instagram
			sleep(1);
		}

		// Save the files in the configured folder and create media entities
		static::saveMedia($la_media);

		Cache::write('instagram_profile_' . $profileName, $la_media, 'instagram');

		return $la_media;
	}


	/**
	 * @param array<\Instagram\Model\Media> $media
	 * @return void
	 */
	protected static function saveMedia(array $media): void {
		$li_mediaFolderId = Configure::read('Instagram.mediaFolderId');
		$ls_path = 'media' . DS;

		if ($li_mediaFolderId) {
			/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_mediaFolder */
			$lo_mediaFoldersTable = FactoryLocator::get('Table')->get('MediaFolders');
			/**
			 * @var \Awyiss\Model\Entity\MediaFolder $lo_mediaFolder
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$lo_mediaFolder = $lo_mediaFoldersTable->findById($li_mediaFolderId)->first();

			if ($lo_mediaFolder) {
				$ls_path = $lo_mediaFolder->path . DS;
			}
			else {
				$li_mediaFolderId = 1;
			}
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_mediaTable */
		$lo_mediaTable = FactoryLocator::get('Table')->get('Media');

		foreach ($media as $li_key => $lo_media) {
			$ls_url = $lo_media->isVideo() && $lo_media->getVideoUrl() ? $lo_media->getVideoUrl() : $lo_media->getDisplaySrc();

			$ls_fileName = substr(str_replace('/', '-', parse_url($ls_url, PHP_URL_PATH)), 1);
			$ls_parts = explode('.', $ls_fileName);
			$ls_extension = end($ls_parts);

			$ls_fileName = $lo_media->getShortCode() . '.' . $ls_extension;

			$ls_content = file_get_contents($ls_url);

			if (!$ls_content) {
				/** @noinspection PhpVariableNamingConventionInspection */
				unset($media[$li_key]);
				continue;
			}

			$lb_fileSaved = file_put_contents(WWW_ROOT . $ls_path . $ls_fileName, $ls_content);

			if (!$lb_fileSaved) {
				/** @noinspection PhpVariableNamingConventionInspection */
				unset($media[$li_key]);
				continue;
			}

			$ls_mimeType = mime_content_type(WWW_ROOT . $ls_path . $ls_fileName);
			$la_knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $ls_mimeType));
			$ls_realExtension = current($la_knownExtensions);
			if ($ls_realExtension === 'jpeg') {
				$ls_realExtension = 'jpg';
			}

			// If instagram thinks it'd be funny to give us a file with the wrong extension, we'll fix it
			if ($ls_extension !== $ls_realExtension) {
				$ls_newFileName = $lo_media->getShortCode() . '.' . $ls_realExtension;
				rename(WWW_ROOT . $ls_path . $ls_fileName, WWW_ROOT . $ls_path . $ls_newFileName);
				$ls_fileName = $ls_newFileName;
			}

			$lo_mediaEntity = $lo_mediaTable->findOrCreate([
				'name' => $ls_fileName,
				'media_folder_id' => $li_mediaFolderId,
			], function (Media $entity) use ($lo_media, $ls_fileName, $ls_path, $ls_mimeType, $li_mediaFolderId): void {
				// Set the mime type
				$entity->set('mimeType', $ls_mimeType);

				$entity->setAccess('createdOn', true);

				$entity->set([
					'mediaFolderId' => $li_mediaFolderId,
					'name' => $ls_fileName,
					'path' => $ls_path . $ls_fileName,
					'width' => $lo_media->getWidth(),
					'height' => $lo_media->getHeight(),
					'createdOn' => $lo_media->getDate(),
					'preview' => $entity->isImage() ? ProcessStatus::NotRequired : ProcessStatus::Undefined,
					'webp' => in_array($entity->mimeType, ['image/webp', 'image/svg+xml']) ? ProcessStatus::NotRequired : ProcessStatus::Undefined,
				]);
			}, [
				'allowFrontendSave' => true,
				'audit' => [
					'skip' => true,
				],
			]);

			$lo_media->setId($lo_mediaEntity->id);
		}
	}
}
