<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Queue\Queue\Task;
use ZipArchive;


class WebfontDownloadTask extends Task {
	/**
	 * @inheritDoc
	 */
	public ?int $timeout = 60;
	/**
	 * @inheritDoc
	 */
	public ?int $retries = 3;


	/**
	 * @inheritDoc
	 * @param array $data
	 * @param int $jobId
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		$la_fonts = $data['fonts'] ?? [];

		if (!$la_fonts) {
			return;
		}

		foreach ($la_fonts as $li_key => $la_font) {
			// Local path for the font
			$ls_fontPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'font' . DS . $la_font['id'];

			// Convert the font variants to the correct format
			foreach ($la_font['variants'] as $li_variantKey => $lx_variant) {
				if ($lx_variant === '400i') {
					$la_font['variants'][ $li_variantKey ] = 'italic';
				}
				elseif ((int)$lx_variant === 400) {
					$la_font['variants'][ $li_variantKey ] = 'regular';
				}
				elseif (str_ends_with($lx_variant, 'i')) {
					$la_font['variants'][ $li_variantKey ] .= 'talic';
				}

				// Update the font variants in the outer array
				$la_fonts[ $li_key ]['variants'][ $li_variantKey ] = $la_font['variants'][ $li_variantKey ];

				if (
					file_exists($ls_fontPath . DS . $la_font['id'] . '-' . $la_font['version'] . '-latin-' . $la_font['variants'][ $li_variantKey ] . '.woff2') ||
					file_exists($ls_fontPath . DS . $la_font['id'] . '-' . $la_font['version'] . '-latin_latin-ext-' . $la_font['variants'][ $li_variantKey ] . '.woff2')
				) {
					// Remove the variant from the list if it already exists
					unset($la_font['variants'][ $li_variantKey ]);
				}
			}

			// Skip if there are no variants to download
			if (!$la_font['variants']) {
				continue;
			}

			// Build the query data
			$la_queryData = [
				'download' => 'zip',
				'subsets' => 'latin,latin-ext',
				'variants' => implode(',', $la_font['variants']),
				'formats' => 'woff2',
			];

			// External API URL
			$ls_downloadUrl = 'https://gwfh.mranftl.com/api/fonts/' . $la_font['id'] . '?' . http_build_query($la_queryData);

			// Local path for the downloaded zip
			$ls_downloadPath = $ls_fontPath . '.zip';

			// Create the font directory if it doesn't exist
			if (!is_dir($ls_fontPath)) {
				mkdir($ls_fontPath, 0750, true);
			}

			// Download the Zip
			$ls_fileData = file_get_contents($ls_downloadUrl);
			file_put_contents($ls_downloadPath, $ls_fileData);

			// Unzip the file
			$lo_zip = new ZipArchive();
			$lo_zip->open($ls_downloadPath);
			$lo_zip->extractTo($ls_fontPath);
			$lo_zip->close();

			// Remove the zip file
			unlink($ls_downloadPath);
		}

		$this->generateScssFile($la_fonts);
	}


	/**
	 * Generate the SCSS file for the webfonts
	 *
	 * @param array $fonts
	 * @return void
	 */
	protected function generateScssFile(array $fonts): void {
		$ls_fileContents = '';

		foreach ($fonts as $la_font) {
			// Local path for the font
			$ls_fontPath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'font' . DS . $la_font['id'];

			foreach ($la_font['variants'] as $li_variantKey => $lx_variant) {
				$li_fontWeight = in_array($lx_variant, ['regular', 'italic'], true) ? 400 : (int)$lx_variant;
				$ls_fontStyle = str_ends_with($lx_variant, 'italic') ? 'italic' : 'normal';
				$ls_fileName = $la_font['id'] . '-' . $la_font['version'] . '-latin_latin-ext-' . $la_font['variants'][ $li_variantKey ] . '.woff2';

				if (!file_exists($ls_fontPath . DS . $la_font['id'] . '-' . $la_font['version'] . '-latin_latin-ext-' . $la_font['variants'][ $li_variantKey ] . '.woff2')) {
					$ls_fileName = $la_font['id'] . '-' . $la_font['version'] . '-latin-' . $la_font['variants'][ $li_variantKey ] . '.woff2';
				}

				$ls_fileContents .= $this->getFontFaceTemplate($la_font['id'], $la_font['name'], $ls_fontStyle, $li_fontWeight, $ls_fileName);
			}
		}

		file_put_contents(ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'scss' . DS . 'webfonts.scss', $ls_fileContents);
	}


	/**
	 * @param string $fontId
	 * @param string $fontName
	 * @param string $fontStyle
	 * @param int $fontWeight
	 * @param string $fileName
	 * @return string
	 */
	protected function getFontFaceTemplate(string $fontId, string $fontName, string $fontStyle, int $fontWeight, string $fileName): string {
		return <<<EOT
@font-face {
    font-display:swap;
    font-family:'$fontName';
    font-style:$fontStyle;
    font-weight:$fontWeight;
    src:url('../font/$fontId/$fileName') format('woff2');
}


EOT;
	}
}
