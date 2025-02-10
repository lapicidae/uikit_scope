<?php

/**
 * Class UIkitScopeManager
 * Manages the scoping and caching of UIkit CSS files.
 */
class UIkitScopeManager
{
	private string $cssContent = '';
	private string $cacheDir;
	private string $outDir;
	private int $cacheLifetime = 86400;
	private string $userAgent = 'Mozilla/5.0 (compatible; UIkitScopeManager/1.0; +https://github.com/lapicidae/uikit_scope)';
	private array $cssCache = [];
	private uikit_scope_logger $logger;

	/**
	 * UIkitScopeManager constructor.
	 * Initializes directories and logger.
	 */
	public function __construct()
	{
		$this->cacheDir = rtrim(rex_path::addonCache('uikit_scope'), '/');
		$this->outDir = rtrim(rex_path::addonAssets('uikit_scope', 'css/generated'), '/');

		$this->ensureDirectory($this->cacheDir);
		$this->ensureDirectory($this->outDir);

		$this->logger = new uikit_scope_logger();
	}

	/**
	 * Ensures the given directory exists.
	 *
	 * @param string $dir Directory path
	 * @throws Exception If the directory cannot be created
	 */
	private function ensureDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			if (!rex_dir::create($dir)) {
				$this->logger->logError("Failed to create directory: {$dir}");
				throw new Exception("Failed to create directory: {$dir}");
			}
		}
	}

	/**
	 * Sets the source path for the CSS file.
	 *
	 * @param string $sourcePath URL or local file path
	 * @throws InvalidArgumentException If the source is invalid
	 * @throws RuntimeException If the CSS content cannot be loaded
	 */
	public function setSourcePath(string $sourcePath): void
	{
		try {
			if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
				$this->cssContent = $this->getExternalCSS($sourcePath);
				$this->logger->logDebug("CSS source identified as URL: {$sourcePath}");
			} elseif (file_exists($sourcePath)) {
				$this->cssContent = rex_file::get($sourcePath, '');
				$this->logger->logDebug("CSS source identified as local file: {$sourcePath}");
			} else {
				$this->logger->logError("CSS source unknown: {$sourcePath}");
				throw new InvalidArgumentException("CSS source unknown: {$sourcePath}");
			}

			if (empty($this->cssContent)) {
				$this->logger->logError("Failed to load CSS content from source: {$sourcePath}");
				throw new RuntimeException("Failed to load CSS content from source: {$sourcePath}");
			}
		} catch (Exception $e) {
			$this->logger->logError("Failed to set source path: {$e->getMessage()}");
			throw $e;
		}
	}

	/**
	 * Downloads content from a given URL.
	 *
	 * @param string $url URL to fetch
	 * @return string|false The content or false on failure
	 */
	private function download(string $url): string|false
	{
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

			$content = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($content === false || $httpCode < 200 || $httpCode >= 300) {
				$error = curl_error($ch);
				$this->logger->logError("cURL request failed for {$url}: {$error} (HTTP Code: {$httpCode})");
				return false;
			}
			return $content;
		} else {
			$context = stream_context_create([
				'http' => [
					'method' => 'GET',
					'timeout' => 20,
					'user_agent' => $this->userAgent,
				],
			]);
			$content = file_get_contents($url, false, $context);
			if ($content === false) {
				$this->logger->logError("file_get_contents failed for {$url}");
				return false;
			}
			return $content;
		}
	}

	/**
	 * Fetches a version number from a given URL.
	 *
	 * @param string $url URL of the version file
	 * @param string $addon Addon name for cache handling
	 * @param string $fallback Fallback version if retrieval fails
	 * @return string The fetched or fallback version
	 */
	public function fetchVersion(string $url, string $addon, string $fallback): string
	{
		try {
			$cacheSec = $this->cacheLifetime;
			$cacheFile = rex_path::addonCache($addon, hash('adler32', $url) . '.json');
			
			$this->logger->logDebug("Checking cache file: $cacheFile");
			$cache = rex_file::getCache($cacheFile);

			if ($cache && isset($cache['timestamp'], $cache['version']) && (time() - $cache['timestamp'] < $cacheSec)) {
				$this->logger->logDebug("Cache hit. Using cached version: " . $cache['version']);
				return $cache['version'];
			}

			$this->logger->logDebug("Cache miss or expired. Fetching new version from: $url");

			$json = $this->download($url);
			if ($json === false) {
				return $fallback;
			}

			$data = json_decode($json, true);
			if (json_last_error() !== JSON_ERROR_NONE || !isset($data['version'])) {
				$errorMsg = json_last_error_msg();
				$this->logger->logError("Failed to decode JSON from {$url}: {$errorMsg}");
				if (file_exists($cacheFile)) {
					unlink($cacheFile);
				}
				return $fallback;
			}

			$version = $data['version'];
			rex_file::putCache($cacheFile, ['timestamp' => time(), 'version' => $version]);
			return $version;
		} catch (Exception $e) {
			$this->logger->logError("Error fetching version for addon {$addon} from {$url}: {$e->getMessage()}");
			return $fallback;
		}
	}


		/**
	 * Retrieves external CSS content, utilizing caching for efficiency.
	 *
	 * @param string $url The URL of the CSS file.
	 * @return string The retrieved CSS content.
	 * @throws RuntimeException If caching fails.
	 * @throws Exception If fetching the CSS fails.
	 */
	private function getExternalCSS(string $url): string
	{
		try {
			$cacheFilePath = $this->cacheDir . '/' . hash('adler32', $url) . '.json';

			$cachedContent = $this->getCachedCSS($cacheFilePath, $url);
			if ($cachedContent !== null) {
				return $cachedContent;
			}

			$cssContent = $this->fetchCSSContent($url);

			$cacheData = [
				'timestamp' => time(),
				'hash' => hash('adler32', $cssContent),
				'content' => base64_encode($cssContent)
			];

			if (!rex_file::putCache($cacheFilePath, json_encode($cacheData))) {
				$this->logger->logError("Failed to write cache file: $cacheFilePath");
				throw new RuntimeException("Failed to write cache file: $cacheFilePath");
			}

			$this->logger->logDebug("CSS content downloaded and cached at: $cacheFilePath");

			return $cssContent;

		} catch (Exception $e) {
			$this->logger->logError("Failed to fetch CSS from URL: $url - Error: " . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Retrieves cached CSS content if it is still valid.
	 *
	 * @param string $cacheFilePath The path to the cache file.
	 * @param string $url The URL of the CSS file.
	 * @return string|null The cached CSS content or null if expired or not found.
	 */
	private function getCachedCSS(string $cacheFilePath, string $url): ?string
	{
		if (file_exists($cacheFilePath)) {
			$cacheContent = rex_file::getCache($cacheFilePath);
			if ($cacheContent === false) {
				$this->logger->logDebug("Cache file exists but could not be read.");
				return null;
			}

			$cacheData = json_decode($cacheContent, true);
			$cachedTimestamp = $cacheData['timestamp'] ?? 0;

			if (isset($cacheData['content'])) {
				$cachedContent = base64_decode($cacheData['content']);

				$this->logger->logDebug("Cache found. Timestamp: " . date('c', $cachedTimestamp));

				if (time() < ($cachedTimestamp + $this->cacheLifetime)) {
					$this->logger->logDebug("Using cached CSS.");
					return $cachedContent;
				}

				$this->logger->logDebug("Cache expired. Fetching new CSS from URL.");
			}
		} else {
			$this->logger->logDebug("No cache file found. Fetching CSS from URL.");
		}
		return null;
	}

	/**
	 * Fetches CSS content from a given URL.
	 *
	 * @param string $url The URL to fetch CSS from.
	 * @return string The fetched CSS content.
	 * @throws RuntimeException If the download fails.
	 */
	private function fetchCSSContent(string $url): string
	{
		$cssContent = $this->download($url);
		if ($cssContent === false) {
			$this->logger->logError("Failed to download CSS content from URL: $url");
			throw new RuntimeException("Failed to download CSS content from URL: $url");
		}
		return $cssContent;
	}

	/**
	 * Filters CSS content based on a given selector and stores the result.
	 *
	 * @param string $selector The CSS selector to filter.
	 * @param string|null $outputSelector The output selector (optional).
	 * @param string|null $cssOutDir The output directory (optional).
	 * @param string|null $sourcePath The source path for the CSS file (optional).
	 * @return object An object containing the URL and file path of the generated CSS.
	 * @throws RuntimeException If writing the file fails.
	 */
	public function filterCSS(string $selector, ?string $outputSelector = null, ?string $cssOutDir = null, ?string $sourcePath = null): object
	{
		$this->logger->logDebug("Starting CSS filtering for selector: {$selector}");

		if ($sourcePath) {
			$this->logger->logDebug("Setting source path: {$sourcePath}");
			$this->setSourcePath($sourcePath);
		}

		try {
			$cssOutDir = $cssOutDir ?: $this->outDir;
			$this->ensureDirectory($cssOutDir);

			$cssFile = $cssOutDir . '/uikit-scope-' . hash('adler32', $selector . $this->cssContent . $cssOutDir . ($outputSelector ?? $selector)) . '.css';
			$cssUrl = rex_url::addonAssets('uikit_scope', 'css/generated/' . basename($cssFile));


			if (file_exists($cssFile) && filesize($cssFile) > 0) {
				$this->logger->logDebug("Generated CSS file found: {$cssFile}");
				return (object)['url' => $cssUrl, 'path' => $cssFile]; // Return object
			}

			$filteredCSS = $this->filterCSSBySelector($selector, $outputSelector);
			$this->logger->logDebug("CSS successfully filtered for selector: {$selector}");

			if (rex_file::put($cssFile, $filteredCSS)) {
				$this->logger->logDebug("Filtered CSS written to file: {$cssFile}");
				return (object)['url' => $cssUrl, 'path' => $cssFile]; // Return object
			} else {
				$this->logger->logWarning("Failed to write filtered CSS to file: {$cssFile}");
				throw new RuntimeException("Failed to write filtered CSS to file: {$cssFile}");
			}

		} catch (Exception $e) {
			$this->logger->logError("Error filtering CSS: {$e->getMessage()}");
			throw $e;
		}
	}

	/**
	 * Extracts and formats CSS rules for a specific selector.
	 *
	 * @param string $selector The CSS selector to extract.
	 * @param string|null $outputSelector The output selector (optional).
	 * @return string The formatted CSS block.
	 */
	private function filterCSSBySelector(string $selector, ?string $outputSelector = null): string
	{
		$outputSelector = $outputSelector ?: $selector;

		if (isset($this->cssCache[$selector . ($outputSelector ?? '')])) {
			$this->logger->logDebug("CSS block for selector '{$selector}' (output: '{$outputSelector}') found in cache.");
			return $this->cssCache[$selector . ($outputSelector ?? '')];
		}

		$pattern = '/' . preg_quote($selector, '/') . '\s*\{(.*?)\}/s';
		if (preg_match($pattern, $this->cssContent, $matches)) {
			$formattedCss = $this->formatCss(trim($matches[1]));
			$targetSelector = $this->validateSelector($outputSelector);

			$cssContent = "{$targetSelector} {\n    {$formattedCss}\n}\n";

			$this->cssCache[$selector . ($outputSelector ?? '')] = $cssContent;

			return $cssContent;
		} else {
			$this->logger->logWarning("No CSS rules found for selector: $selector");
			return '';
		}
	}

	/**
	 * Formats raw CSS rules for consistency.
	 *
	 * @param string $rawCss The raw CSS content.
	 * @return string The formatted CSS content.
	 */
	private function formatCss(string $rawCss): string
	{
		$rules = array_filter(array_map('trim', explode(';', $rawCss)));
		return implode(";\n    ", $rules) . ';';
	}

	/**
	 * Ensures the selector has a valid prefix.
	 *
	 * @param string $selector The CSS selector to validate.
	 * @param string|null $prefix The prefix to apply if needed (default: '.').
	 * @return string The validated selector.
	 */
	private function validateSelector(string $selector, ?string $prefix = null): string
	{
		$prefix = $prefix ?? '.';

		if (!empty($prefix) && !preg_match('/^[.#\[\*+\~^\$:]/', $selector)) {
			return $prefix . $selector;
		}
		return $selector;
	}
}
