<?php declare(strict_types=1);


namespace Awyiss\Utility\Media;


use Awyiss\Model\Enum\ResizeStrategy;


/**
 * Class MediaRenderOptions
 * Holds the options for rendering media elements
 */
class MediaRenderOptions {
	/**
	 * When any key in the array provided to the `with` method
	 * uses this value, the value will be preserved.
	 */
	final public const string PRESERVE_VALUE = 'PRESERVE_VALUE';


	/**
	 * @param bool $allowUpscale
	 * @param float|int|null $aspectRatio
	 * @param array $attributes
	 * @param string|false|null $backgroundColor
	 * @param float|int $baseWidth
	 * @param array<float, array{
	 *     baseWidth: float|null,
	 *     breakpoint: float,
	 *     columnWidth: float|null,
	 *     width: float|null,
	 *     height: float|null,
	 *     resizeStrategy: \Awyiss\Model\Enum\ResizeStrategy|null
	 * }> $breakpoints
	 * @param float|int $columnWidth
	 * @param float|int|null $height
	 * @param bool $include2x
	 * @param bool $lazyload
	 * @param float|null $minBreakpoint
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $resizeStrategy
	 * @param bool $responsive
	 * @param string|null $selector
	 * @param float|int|false|null $singleColumnBreakpoint
	 * @param bool $strictSize
	 * @param float|int|null $width
	 */
	public function __construct(
		protected bool $allowUpscale = false,
		protected float|int|null $aspectRatio = null,
		protected array $attributes = [],
		protected string|false|null $backgroundColor = null,
		protected float|int $baseWidth = 3840,
		protected array $breakpoints = [],
		protected float|int $columnWidth = 100.00,
		protected float|int|null $height = null,
		protected bool $include2x = true,
		protected bool $lazyload = true,
		protected ?float $minBreakpoint = null,
		protected ResizeStrategy|string|int $resizeStrategy = ResizeStrategy::Contain,
		protected bool $responsive = true,
		protected ?string $selector = null,
		protected float|int|false|null $singleColumnBreakpoint = null,
		protected bool $strictSize = false,
		protected float|int|null $width = null,
	) {
		if (is_int($this->aspectRatio)) {
			$this->aspectRatio = (float)$this->aspectRatio;
		}

		if (is_int($this->baseWidth)) {
			$this->baseWidth = (float)$this->baseWidth;
		}

		$this->breakpoints = $this->normalizeBreakpoints($this->breakpoints);

		if (is_int($this->columnWidth)) {
			$this->columnWidth = (float)$this->columnWidth;
		}

		if (is_int($this->height)) {
			$this->height = (float)$this->height;
		}

		// Normalize the resize strategy
		$this->resizeStrategy = ResizeStrategy::normalize($this->resizeStrategy);

		if (is_int($this->singleColumnBreakpoint)) {
			$this->singleColumnBreakpoint = (float)$this->singleColumnBreakpoint;
		}

		if (is_int($this->width)) {
			$this->width = (float)$this->width;
		}
	}


	/**
	 * @return bool
	 */
	public function getAllowUpscale(): bool {
		return $this->allowUpscale;
	}


	/**
	 * @return float|null
	 */
	public function getAspectRatio(): ?float {
		return $this->aspectRatio;
	}


	/**
	 * @return array
	 */
	public function getAttributes(): array {
		return $this->deepCopy($this->attributes);
	}


	/**
	 * @return string|false|null
	 */
	public function getBackgroundColor(): string|false|null {
		return $this->backgroundColor;
	}


	/**
	 * @return float
	 */
	public function getBaseWidth(): float {
		return $this->baseWidth;
	}


	/**
	 * @return array
	 */
	public function getBreakpoints(): array {
		return $this->deepCopy($this->breakpoints);
	}


	/**
	 * @return float
	 */
	public function getColumnWidth(): float {
		return $this->columnWidth;
	}


	/**
	 * @return float|null
	 */
	public function getHeight(): ?float {
		return $this->height;
	}


	/**
	 * @return bool
	 */
	public function getInclude2x(): bool {
		return $this->include2x;
	}


	/**
	 * @return bool
	 */
	public function getLazyload(): bool {
		return $this->lazyload;
	}


	/**
	 * @return float|null
	 */
	public function getMinBreakpoint(): ?float {
		return $this->minBreakpoint;
	}


	/**
	 * @return \Awyiss\Model\Enum\ResizeStrategy
	 */
	public function getResizeStrategy(): ResizeStrategy {
		return $this->resizeStrategy;
	}


	/**
	 * @return bool
	 */
	public function getResponsive(): bool {
		return $this->responsive;
	}


	/**
	 * @return string|null
	 */
	public function getSelector(): ?string {
		return $this->selector;
	}


	/**
	 * @return float|false|null
	 */
	public function getSingleColumnBreakpoint(): float|false|null {
		return $this->singleColumnBreakpoint;
	}


	/**
	 * @return bool
	 */
	public function getStrictSize(): bool {
		return $this->strictSize;
	}


	/**
	 * @return float|null
	 */
	public function getWidth(): ?float {
		return $this->width;
	}


	/**
	 * @param bool $allowUpscale
	 * @return $this
	 */
	public function withAllowUpscale(bool $allowUpscale = true): static {
		return $this->with(['allowUpscale' => $allowUpscale]);
	}


	/**
	 * @param float|int|null $aspectRatio
	 * @return $this
	 */
	public function withAspectRatio(float|int|null $aspectRatio): static {
		return $this->with(['aspectRatio' => $aspectRatio]);
	}


	/**
	 * @param array $attributes
	 * @return $this
	 */
	public function withAttributes(array $attributes): static {
		return $this->with(['attributes' => $attributes]);
	}


	/**
	 * @param string|false|null $backgroundColor
	 * @return $this
	 */
	public function withBackgroundColor(string|false|null $backgroundColor): static {
		return $this->with(['backgroundColor' => $backgroundColor]);
	}


	/**
	 * @param float|int $baseWidth
	 * @return $this
	 */
	public function withBaseWidth(float|int $baseWidth): static {
		return $this->with(['baseWidth' => $baseWidth]);
	}


	/**
	 * @param float|int $breakpoint
	 * @param array $options
	 * @return $this
	 */
	public function withBreakpoint(float|int $breakpoint, array $options = []): static {
		$breakpoints = $this->breakpoints;
		$breakpoints[ $breakpoint ] = $options;

		return $this->with(['breakpoints' => $this->normalizeBreakpoints($breakpoints)]);
	}


	/**
	 * @param array $breakpoints
	 * @return $this
	 */
	public function withBreakpoints(array $breakpoints): static {
		return $this->with(['breakpoints' => $breakpoints]);
	}


	/**
	 * @param float|int $columnWidth
	 * @return $this
	 */
	public function withColumnWidth(float|int $columnWidth): static {
		return $this->with(['columnWidth' => $columnWidth]);
	}


	/**
	 * @param float|int|null $height
	 * @return $this
	 */
	public function withHeight(float|int|null $height): static {
		return $this->with(['height' => $height]);
	}


	/**
	 * @param bool $include2x
	 * @return $this
	 */
	public function withInclude2x(bool $include2x): static {
		return $this->with(['include2x' => $include2x]);
	}


	/**
	 * @param bool $lazyload
	 * @return $this
	 */
	public function withLazyload(bool $lazyload): static {
		return $this->with(['lazyload' => $lazyload]);
	}


	/**
	 * @param float|null $minBreakpoint
	 * @return $this
	 */
	public function withMinBreakpoint(?float $minBreakpoint): static {
		return $this->with(['minBreakpoint' => $minBreakpoint]);
	}


	/**
	 * @param \Awyiss\Model\Enum\ResizeStrategy|string|int $resizeStrategy
	 * @return $this
	 */
	public function withResizeStrategy(ResizeStrategy|string|int $resizeStrategy): static {
		return $this->with(['resizeStrategy' => $resizeStrategy]);
	}


	/**
	 * @param bool $responsive
	 * @return $this
	 */
	public function withResponsive(bool $responsive = true): static {
		return $this->with(['responsive' => $responsive]);
	}


	/**
	 * @param string|null $selector
	 * @return $this
	 */
	public function withSelector(?string $selector): static {
		return $this->with(['selector' => $selector]);
	}


	/**
	 * @param float|int|false|null $singleColumnBreakpoint
	 * @return $this
	 */
	public function withSingleColumnBreakpoint(float|int|false|null $singleColumnBreakpoint): static {
		return $this->with(['singleColumnBreakpoint' => $singleColumnBreakpoint]);
	}


	/**
	 * @param bool $strictSize
	 * @return $this
	 */
	public function withStrictSize(bool $strictSize = true): static {
		return $this->with(['strictSize' => $strictSize]);
	}


	/**
	 * @param float|int|null $width
	 * @return $this
	 */
	public function withWidth(float|int|null $width): static {
		return $this->with(['width' => $width]);
	}


	/**
	 * @param array $changes
	 * @return $this
	 */
	public function with(array $changes): static {
		$properties = get_object_vars($this);
		foreach ($properties as $name => $value) {
			$properties[ $name ] = $value;

			if (array_key_exists($name, $changes) && $changes[ $name ] !== self::PRESERVE_VALUE) {
				$properties[ $name ] = $changes[ $name ];
			}
		}

		return new self(...$properties);
	}


	/**
	 * Normalize a single breakpoint.
	 *
	 * @param string|float|int $key
	 * @param array|float|int $value
	 * @return array<float, array{baseWidth: float|null, breakpoint: float, columnWidth: float|null, width: float|null, height: float|null, resizeStrategy: \Awyiss\Model\Enum\ResizeStrategy|null}> $breakpoints
	 * @noinspection LongLine
	 */
	public static function normalizeBreakpoint(string|float|int $key, array|float|int $value): array {
		$options = [
			'aspectRatio' => self::PRESERVE_VALUE,
			'baseWidth' => null,
			'breakpoint' => (float)$key,
			'columnWidth' => self::PRESERVE_VALUE,
			'height' => self::PRESERVE_VALUE,
			'is2x' => false,
			'resizeStrategy' => self::PRESERVE_VALUE,
			'width' => self::PRESERVE_VALUE,
		];

		if (is_numeric($value)) {
			$options['breakpoint'] = (float)$value;

			return $options;
		}

		foreach ($options as $optionKey => $optionValue) {
			if (array_key_exists($optionKey, $value)) {
				$options[ $optionKey ] = $value[ $optionKey ];
			}

			if ($optionKey !== 'resizeStrategy' && !empty($options[ $optionKey ]) && $options[ $optionKey ] !== self::PRESERVE_VALUE) {
				$options[ $optionKey ] = (float)$options[ $optionKey ];
			}
		}

		return $options;
	}


	/**
	 * Normalize the breakpoints array.
	 *
	 * @param array $breakpoints
	 * @return array
	 */
	public static function normalizeBreakpoints(array $breakpoints): array {
		$normalizedBreakpoints = [];

		foreach ($breakpoints as $key => $value) {
			$breakpointOptions = static::normalizeBreakpoint($key, $value);
			$breakpoint = (int)$breakpointOptions['breakpoint'];
			$normalizedBreakpoints[ $breakpoint ] = $breakpointOptions;
		}

		// Sort breakpoints by breakpoint value
		usort($normalizedBreakpoints, function (array $a, array $b): int {
			if ($a['breakpoint'] === $b['breakpoint']) {
				return $b['is2x'] <=> $a['is2x'];
			}

			return $a['breakpoint'] <=> $b['breakpoint'];
		});

		return $normalizedBreakpoints;
	}


	/**
	 * Deep copy an array to ensure immutability.
	 *
	 * @param array $array
	 * @return array
	 */
	protected function deepCopy(array $array): array {
		return array_map(function ($item) {
			return is_array($item) ? $this->deepCopy($item) : $item;
		}, $array);
	}
}
