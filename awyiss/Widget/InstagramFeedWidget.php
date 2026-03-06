<?php declare(strict_types=1);


namespace Awyiss\Widget;


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
use Instagram\Auth\Checkpoint\ImapClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Throwable;


/**
 * Class InstagramFeedWidget
 * Show a list of Instagram posts from a profile
 * Also downloads the images and creates media entities for them
 */
class InstagramFeedWidget extends AbstractWidget {
	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		// Translate using __d() if needed
		return 'Intagram Feed';
	}


	/**
	 * @inheritDoc
	 */
	public static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
		return [
			// A dropdown to select the homepage (for the current language)
			'settings.items' => [
				'columnSpan' => 6,
				'label' => __df('Frontend/InstagramFeed', 'Frontend/Widgets', 'number_of_items'),
				'placeholder' => '6',
				'type' => 'number',
				'value' => $settings['items'] ?? null,
			],

			// A dropdown to select the homepage (for the current language)
			'settings.profileName' => [
				'columnSpan' => 6,
				'label' => __df('Frontend/InstagramFeed', 'Frontend/Widgets', 'profile_name'),
				'placeholder' => Configure::read('Instagram.userName'),
				'value' => $settings['profileName'] ?? null,
			],
		];
	}


	/**
	 * @inheritDoc
	 */
	public static function render(
		array $settings,
		FrontendView $view,
		?MediaRenderOptions $mediaRenderOptions = null,
		?Entity $entity = null,
		?Language $frontendLanguage = null
	): string {
		$credentialsSet = Configure::check('Instagram.userName') && Configure::check('Instagram.password');

		$userName = Configure::read('Instagram.userName');
		$itemLimit = $settings['items'] ?? 6;
		$cacheLifetime = 60 * 60 * 24 * 365;
		$media = [];
		$profileName = $settings['profileName'] ?? $userName;

		$errorMessage = null;
		if ($credentialsSet) {
			try {
				$media = static::fetchMedia($userName, $profileName, $itemLimit, $cacheLifetime);
			}
			catch (Throwable $ex) {
				$errorMessage = $ex->getMessage();
			}
		}

		if ($media) {
			$media = array_slice($media, 0, $itemLimit);

			$mediaIds = array_map(function (Media $media) {
				return $media->id;
			}, $media);

			/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
			$mediaTable = FactoryLocator::get('Table')->get('Media');
			$mediaEntities = $mediaTable->find('all')
				->where(['id IN' => $mediaIds])
				->contain(['MediaResizedImages'])
				->all()
				->indexBy('id')
				->toArray();

			/** @var \Instagram\Model\Media $mediaItem */
			foreach ($media as $mediaItem) {
				$mediaItem->mediaEntity = $mediaEntities[ $mediaItem->getId() ] ?? null;
			}

			// Set the media items for the ResizedImageManager
			ResizedImageManager::setMediaItems($mediaEntities);
		}

		return $view->element('widget/instagram_feed', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'credentialsSet' => $credentialsSet,
			'errorMessage' => $errorMessage,
			'items' => $itemLimit,
			'media' => $media,
			'profileName' => $profileName,
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
		$media = Cache::read('instagram_profile_' . $profileName, 'instagram');

		if (!empty($media)) {
			return $media;
		}

		$cachePool = new FilesystemAdapter('Instagram', $cacheLifetime, TMP . 'instagram_profile');

		$api = new Api($cachePool);

		$imapUserName = Configure::read('Instagram.imapUserName');
		$imapPassword = Configure::read('Instagram.imapPassword');
		$imapServer = Configure::read('Instagram.imapServer');
		if ($imapServer && $imapUserName && $imapPassword) {
			$imapClient = new ImapClient($imapServer, $imapUserName, $imapPassword);
		}

		$api->login($userName, Configure::read('Instagram.password'), $imapClient ?? null);
		$profile = $api->getProfile($profileName);

		$media = $profile->getMedias();
		$mediaCount = count($media);

		while ($mediaCount < $items) {
			$profile = $api->getMoreMedias($profile);
			$newMedia = $profile->getMedias();

			if (empty($newMedia)) {
				break;
			}

			$media = array_merge($media, $newMedia);
			$mediaCount = count($media);

			// avoid 429 Rate limit from Instagram
			sleep(1);
		}

		// Save the files in the configured folder and create media entities
		static::saveMedia($media);

		Cache::write('instagram_profile_' . $profileName, $media, 'instagram');

		return $media;
	}


	/**
	 * @param array<\Instagram\Model\Media> $media
	 * @return void
	 */
	protected static function saveMedia(array $media): void {
		$mediaFolderId = Configure::read('Instagram.mediaFolderId');
		$path = 'media' . DS;

		if ($mediaFolderId) {
			/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFolder */
			$mediaFoldersTable = FactoryLocator::get('Table')->get('MediaFolders');
			/**
			 * @var \Awyiss\Model\Entity\MediaFolder $mediaFolder
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$mediaFolder = $mediaFoldersTable->findById($mediaFolderId)->first();

			if ($mediaFolder) {
				$path = $mediaFolder->path . DS;
			}
			else {
				$mediaFolderId = 1;
			}
		}

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = FactoryLocator::get('Table')->get('Media');

		foreach ($media as $key => $mediaItem) {
			$url = $mediaItem->isVideo() && $mediaItem->getVideoUrl() ? $mediaItem->getVideoUrl() : $mediaItem->getDisplaySrc();

			$fileName = substr(str_replace('/', '-', parse_url($url, PHP_URL_PATH)), 1);
			$parts = explode('.', $fileName);
			$extension = end($parts);

			$fileName = $mediaItem->getShortCode() . '.' . $extension;

			$content = file_get_contents($url);

			if (!$content) {
				unset($media[$key]);
				continue;
			}

			$fileSaved = file_put_contents(WWW_ROOT . $path . $fileName, $content);

			if (!$fileSaved) {
				unset($media[$key]);
				continue;
			}

			$mimeType = mime_content_type(WWW_ROOT . $path . $fileName);
			$knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $mimeType));
			$realExtension = current($knownExtensions);
			if ($realExtension === 'jpeg') {
				$realExtension = 'jpg';
			}

			// If instagram thinks it'd be funny to give us a file with the wrong extension, we'll fix it
			if ($extension !== $realExtension) {
				$newFileName = $mediaItem->getShortCode() . '.' . $realExtension;
				rename(WWW_ROOT . $path . $fileName, WWW_ROOT . $path . $newFileName);
				$fileName = $newFileName;
			}

			$mediaEntity = $mediaTable->findOrCreate([
				'name' => $fileName,
				'mediaFolderId' => $mediaFolderId,
			], function (Media $entity) use ($mediaItem, $fileName, $path, $mimeType, $mediaFolderId): void {
				// Set the mime type
				$entity->set('mimeType', $mimeType);

				$entity->setAccess('createdOn', true);

				$entity->patch([
					'mediaFolderId' => $mediaFolderId,
					'name' => $fileName,
					'path' => $path . $fileName,
					'width' => $mediaItem->getWidth(),
					'height' => $mediaItem->getHeight(),
					'createdOn' => $mediaItem->getDate(),
					'preview' => $entity->isImage() ? ProcessStatus::NotRequired : ProcessStatus::Undefined,
					'avif' => in_array($entity->mimeType, ['image/avif', 'image/svg+xml']) ? ProcessStatus::NotRequired : ProcessStatus::Undefined,
					'webp' => in_array($entity->mimeType, ['image/webp', 'image/svg+xml']) ? ProcessStatus::NotRequired : ProcessStatus::Undefined,
				]);
			}, [
				'allowFrontendSave' => true,
				'audit' => [
					'skip' => true,
				],
			]);

			$mediaItem->setId($mediaEntity->id);
		}
	}
}
