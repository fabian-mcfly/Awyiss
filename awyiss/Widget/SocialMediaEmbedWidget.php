<?php declare(strict_types=1);


namespace Awyiss\Widget;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Media\MediaRenderOptions;
use Awyiss\View\BackendView;
use Awyiss\View\FrontendView;


/**
 * Class SocialMediaEmbedWidget
 * Configures and displays social media embeds (YouTube, Vimeo, Instagram, X) with 2-click consent mechanism.
 */
class SocialMediaEmbedWidget extends AbstractWidget {
	/**
	 * Supported social media services
	 *
	 * @var array<string>
	 */
	protected static array $supportedServices = [
		'youtube',
		'vimeo',
		'instagram',
	];
	/**
	 * Extractor methods for each service
	 *
	 * @var array<string, string|callable>
	 */
	protected static array $extractors = [
		'youtube' => 'extractYouTubeId',
		'vimeo' => 'extractVimeoId',
		'instagram' => 'extractInstagramId',
	];


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		return 'Social Media Embed';
	}


	/**
	 * @inheritDoc
	 */
	public static function getFormFields(BackendView $view, ?Language $frontendLanguage = null, ?Language $userLanguage = null, array $settings = []): array {
		$services = array_combine(
			static::$supportedServices,
			array_map(function (string $service): string {
				return __df('Frontend/SocialMediaEmbed', 'Frontend/Widgets', 'service_' . $service);
			}, static::$supportedServices)
		);

		$formFields = [
			'settings.service' => [
				'label' => __df('Frontend/SocialMediaEmbed', 'Frontend/Widgets', 'service'),
				'options' => $services,
				'required' => true,
				'type' => 'select',
				'value' => $settings['service'] ?? null,
				'data-form-updater' => true,
			],
		];

		if (!empty($settings['service'])) {
			$placeholder = match ($settings['service']) {
				'youtube' => '6cEidQxoXCw / https://www.youtube.com/watch?v=6cEidQxoXCw',
				'vimeo' => '928368341 / https://vimeo.com/928368341',
				'instagram' => 'DNVGGL2oGPt / https://www.instagram.com/p/DNVGGL2oGPt',
				default => '',
			};

			$formFields['settings.embedId'] = [
				'label' => __df('Frontend/SocialMediaEmbed', 'Frontend/Widgets', 'embed_id'),
				'placeholder' => $placeholder,
				'required' => true,
				'type' => 'text',
				'value' => $settings['embedId'] ?? null,
			];
		}

		return $formFields;
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
		// Validate settings
		if (
			empty($settings['service']) || empty($settings['embedId']) || !in_array($settings['service'], static::$supportedServices, true)
		) {
			return '';
		}

		// Normalize and validate the embed ID based on service
		$embedId = static::normalizeEmbedId($settings['service'], $settings['embedId']);

		if (!$embedId) {
			return '';
		}

		return $view->element('widget/social_media_embed', [
			'entity' => $entity,
			'frontendLanguage' => $frontendLanguage,
			'mediaRenderOptions' => $mediaRenderOptions,
			'settings' => $settings,
			'embedId' => $embedId,
		]);
	}


	/**
	 * Normalize and validate embed ID based on service type.
	 * Extracts ID from full URLs if needed.
	 *
	 * @param string $service The social media service
	 * @param string $input The user input (URL or ID)
	 * @return string|null The normalized embed ID, or null if invalid
	 */
	protected static function normalizeEmbedId(string $service, string $input): ?string {
		$input = trim($input);

		if (empty($input)) {
			return null;
		}

		// Use the appropriate extractor method
		$extractorMethod = static::$extractors[ $service ] ?? null;
		if (!$extractorMethod) {
			return null;
		}

		if (is_callable($extractorMethod)) {
			return $extractorMethod($input);
		}

		if (is_callable([static::class, $extractorMethod])) {
			return static::{$extractorMethod}($input);
		}

		return null;
	}


	/**
	 * Extract YouTube video ID from URL or return as-is if it looks like an ID
	 *
	 * @param string $input YouTube URL or video ID
	 * @return string|null The video ID
	 * @noinspection PhpUnused
	 */
	protected static function extractYouTubeId(string $input): ?string {
		$input = trim($input, '/');

		// Match standard video ID pattern (11 characters, alphanumeric with - and _)
		if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
			return $input;
		}

		// Try to extract from various YouTube URL formats
		$patterns = [
			'/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
		];

		/** @noinspection PhpLoopCanBeConvertedToArrayAnyInspection */
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $input, $matches)) {
				return $matches[1];
			}
		}

		return null;
	}


	/**
	 * Extract Vimeo video ID from URL or return as-is if it looks like an ID
	 *
	 * @param string $input Vimeo URL or video ID
	 * @return string|null The video ID
	 * @noinspection PhpUnused
	 */
	protected static function extractVimeoId(string $input): ?string {
		$input = trim($input, '/');

		// Match standard numeric ID
		if (preg_match('/^\d+$/', $input)) {
			return $input;
		}

		// Try to extract from Vimeo URL formats
		$patterns = [
			'/vimeo\.com\/(\d+)/',
			'/vimeo\.com\/channels\/[^\/]+\/(\d+)/',
			'/vimeo\.com\/groups\/[^\/]+\/videos\/(\d+)/',
		];

		/** @noinspection PhpLoopCanBeConvertedToArrayAnyInspection */
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $input, $matches)) {
				return $matches[1];
			}
		}

		return null;
	}


	/**
	 * Extract Instagram post ID from URL or return as-is if it looks like an ID
	 *
	 * @param string $input Instagram URL, share code, or post ID
	 * @return string|null The post ID or share code
	 * @noinspection PhpUnused
	 */
	protected static function extractInstagramId(string $input): ?string {
		$input = trim($input, '/');

		// Match Instagram share code pattern (alphanumeric with - and _)
		if (preg_match('/^[a-zA-Z0-9_-]+$/', $input) && strlen($input) >= 8) {
			return $input;
		}

		// Try to extract from Instagram post URL
		if (preg_match('/instagram\.com\/p\/([a-zA-Z0-9_-]+)/', $input, $matches)) {
			return $matches[1];
		}

		// Try to extract from Instagram reel URL
		if (preg_match('/instagram\.com\/reel\/([a-zA-Z0-9_-]+)/', $input, $matches)) {
			return $matches[1];
		}

		return null;
	}


	/**
	 * Register a new social media service with an optional extractor method.
	 *
	 * @param string $service
	 * @param callable|null $extractor
	 * @return void
	 * @noinspection PhpUnused
	 */
	public static function registerService(string $service, ?callable $extractor = null): void {
		if (!in_array($service, static::$supportedServices, true)) {
			static::$supportedServices[] = $service;
		}

		if ($extractor !== null) {
			static::$extractors[ $service ] = $extractor;
		}
	}
}
