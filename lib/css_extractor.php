<?php

/**
 * Class CSSExtractor
 *
 * Extracts, filters, and caches CSS content from a given source.
 */
class CSSExtractor
{
    private string $cssContent;
    private string $cacheDir;
    private string $outDir;
    private string $sourcePath;
    private bool $debugMode;
    private int $cacheLifetime = 86400;

    /**
     * CSSExtractor constructor.
     *
     * Initializes the CSSExtractor object, determines the source of the CSS content
     * (either external URL or local file), and sets up cache directories.
     *
     * @param string $sourcePath The path to the source CSS, either a file path or a URL.
     * @param bool $debugMode Enable or disable debug mode for logging.
     *
     * @throws Exception If the CSS source is invalid or cannot be loaded.
     */
    public function __construct(string $sourcePath, bool $debugMode = false)
    {
        $this->sourcePath = $sourcePath;
        $this->debugMode = $debugMode;

        $this->logDebug("Initialized CSSExtractor with source: $sourcePath");

        $this->cacheDir = rtrim(rex_path::addonCache('uikit_scope'), '/');
        $this->outDir = rtrim(rex_path::addonAssets('uikit_scope', 'css/generated'), '/');

        if (!is_dir($this->cacheDir)) {
            rex_dir::create($this->cacheDir);
        }
        if (!is_dir($this->outDir)) {
            rex_dir::create($this->outDir);
        }

        if (filter_var($sourcePath, FILTER_VALIDATE_URL)) {
            $this->logDebug("Handling external source: $sourcePath");
            $this->cssContent = $this->getExternalCSS($sourcePath);
        } elseif (file_exists($sourcePath)) {
            $this->logDebug("Handling internal source: $sourcePath");
            $this->cssContent = rex_file::get($sourcePath, '');
        } else {
            throw new Exception("CSS source not found: {$sourcePath}");
        }

        if (!$this->cssContent) {
            throw new Exception("Failed to load CSS from source: {$sourcePath}");
        }
    }

    /**
     * Fetches and returns the CSS content from an external URL.
     *
     * Handles caching of the CSS based on HTTP headers like ETag and Last-Modified.
     * Utilizes cURL for fetching the content if available; otherwise falls back on file_get_contents.
     *
     * @param string $url The URL from which to fetch the CSS content.
     *
     * @return string The fetched CSS content.
     *
     * @throws Exception If fetching the CSS fails or if caching errors occur.
     */
    private function getExternalCSS(string $url): string
    {
        $cacheFilePath = $this->cacheDir . '/' . hash('adler32', $url) . '.json';
        $userAgent = 'Mozilla/5.0 (compatible; CSSExtractor/1.0; +https://github.com/lapicidae/uikit_scope)';
        
        $eTag = null;
        $lastModifiedTimestamp = null;
        $headers = [];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_FILETIME, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("cURL request failed for URL: $url - Error: $error");
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 200 || $httpCode >= 300) {
                curl_close($ch);
                throw new Exception("HTTP error $httpCode during request for URL: $url");
            }

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            $headersRaw = substr($response, 0, $headerSize);
            $content = substr($response, $headerSize);

            foreach (explode("\r\n", $headersRaw) as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $headers[strtolower(trim($key))] = trim($value);
                }
            }

            $eTag = $headers['etag'] ?? null;
            if (is_array($eTag)) {
                $eTag = end($eTag);
            }
            $eTag = $eTag ? trim($eTag, '"') : null;

            $lastModifiedHeader = $headers['last-modified'] ?? null;
            if (is_array($lastModifiedHeader)) {
                $lastModifiedHeader = end($lastModifiedHeader);
            }
            $lastModifiedTimestamp = $lastModifiedHeader ? strtotime($lastModifiedHeader) : null;
            if ($lastModifiedTimestamp === false) {
                $lastModifiedTimestamp = null;
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'user_agent' => $userAgent,
                    'timeout' => 10
                ]
            ]);
            $headersRaw = get_headers($url, 1, $context);
            if ($headersRaw === false || !isset($headersRaw[0]) || strpos($headersRaw[0], '200') === false) {
                throw new Exception("Failed to fetch headers for URL: $url");
            }

            $headers = array_change_key_case($headersRaw, CASE_LOWER);

            $eTag = $headers['etag'] ?? null;
            if (is_array($eTag)) {
                $eTag = end($eTag);
            }
            $eTag = $eTag ? trim($eTag, '"') : null;

            $lastModifiedHeader = $headers['last-modified'] ?? null;
            if (is_array($lastModifiedHeader)) {
                $lastModifiedHeader = end($lastModifiedHeader);
            }
            $lastModifiedTimestamp = $lastModifiedHeader ? strtotime($lastModifiedHeader) : null;
            if ($lastModifiedTimestamp === false) {
                $lastModifiedTimestamp = null;
            }
        }

        if (file_exists($cacheFilePath)) {
            $cacheContent = rex_file::getCache($cacheFilePath);
            if ($cacheContent === false) {
                throw new Exception("Failed to read cache file: $cacheFilePath");
            }
            $cacheData = json_decode($cacheContent, true);
            $cachedTimestamp = $cacheData['timestamp'] ?? 0;
            $cachedETag = $cacheData['eTag'] ?? null;
            $cachedLastModified = $cacheData['lastModified'] ?? null;

            if (array_key_exists('content', $cacheData)) {
                $cachedContent = base64_decode($cacheData['content']);

                if ($eTag && $cachedETag && $eTag === $cachedETag && time() < ($cachedTimestamp + $this->cacheLifetime)) {
                    $this->logDebug("Using cached CSS based on ETag check.");
                    return $cachedContent;
                }
                if ($lastModifiedTimestamp && $cachedLastModified && $lastModifiedTimestamp === $cachedLastModified && time() < ($cachedTimestamp + $this->cacheLifetime)) {
                    $this->logDebug("Using cached CSS based on Last-Modified check.");
                    return $cachedContent;
                }
                if (time() < ($cachedTimestamp + $this->cacheLifetime)) {
                    $this->logDebug("Using cached CSS based on cache lifetime.");
                    return $cachedContent;
                }
            }
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

            $cssContent = curl_exec($ch);
            if ($cssContent === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("cURL GET request failed for URL: $url - Error: $error");
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 200 || $httpCode >= 300) {
                curl_close($ch);
                throw new Exception("HTTP error $httpCode during GET request for URL: $url");
            }
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 20,
                    'user_agent' => $userAgent
                ]
            ]);
            $cssContent = file_get_contents($url, false, $context);
            if ($cssContent === false) {
                throw new Exception("Failed to load CSS content from URL: $url");
            }
        }

        $cacheData = [
            'timestamp' => time(),
            'hash' => hash('adler32', $cssContent),
            'eTag' => $eTag,
            'lastModified' => $lastModifiedTimestamp,
            'content' => base64_encode($cssContent)
        ];
        if (rex_file::putCache($cacheFilePath, json_encode($cacheData)) === false) {
            throw new Exception("Failed to write cache file: $cacheFilePath");
        }

        $this->logDebug("CSS content downloaded and cached at: $cacheFilePath" .
            ($eTag ? " with ETag: $eTag" : ($lastModifiedTimestamp ? " with Last-Modified timestamp: $lastModifiedTimestamp" : "")));

        return $cssContent;
    }

    /**
     * Filters and outputs CSS based on a given selector.
     *
     * The filtered CSS is saved in the specified output directory or a default directory.
     * It checks for an existing cache and uses it if no changes are detected.
     *
     * @param string $selector The CSS selector to filter.
     * @param string|null $outputSelector The output selector to apply.
     * @param string|null $customOutDir The custom output directory for saving the filtered CSS.
     * @param bool $forceWrite Whether to force overwrite the existing CSS file.
     *
     * @return string|null The name of the output CSS file, or null if no matching selector is found.
     */
    public function filterCSS(string $selector, ?string $outputSelector = null, ?string $customOutDir = null, bool $forceWrite = false): ?string
    {
        $outDir = $customOutDir ? rtrim($customOutDir, '/') : $this->outDir;
        if (!is_dir($outDir)) {
            rex_dir::create($outDir);
        }

        $fileHash = hash('adler32', $this->sourcePath . $selector);
        $cacheFilePath = $this->cacheDir . '/' . $fileHash . '.json';
        $outFileName = 'uikit_scope_' . $fileHash . '.css';
        $outFilePath = $outDir . '/' . $outFileName;

        $existingHash = null;
        $cachedContent = null;
        if (file_exists($cacheFilePath)) {
            $cacheData = json_decode(rex_file::getCache($cacheFilePath), true);
            $existingHash = $cacheData['hash'] ?? null;
            $cachedContent = $cacheData['content'] ?? null;

            if (!$forceWrite && $existingHash && file_exists($outFilePath)) {
                $currentFileHash = hash_file('adler32', $outFilePath);

                if ($existingHash === $currentFileHash) {
                    $this->logDebug("No changes detected. Skipping rewrite.");
                    return $outFileName;
                }
            }
        }

        $pattern = '/' . preg_quote($selector, '/') . '\s*\{(.*?)\}/s';
        if (preg_match($pattern, $this->cssContent, $matches)) {
            $formattedCss = $this->formatCss(trim($matches[1]));
            $targetSelector = $this->validateSelector($outputSelector ?? $selector);
            $cssContent = "{$targetSelector} {\n    {$formattedCss}\n}\n";

            $newHash = hash('adler32', $cssContent);

            if (!$forceWrite && $existingHash === $newHash && file_exists($outFilePath)) {
                $this->logDebug("Filtered CSS unchanged, skipping cache update.");
                return $outFileName;
            }

            rex_file::put($outFilePath, $cssContent);

            $cacheData = [
                'timestamp' => time(),
                'hash' => $newHash,
                'content' => base64_encode($cssContent),
            ];

            rex_file::putCache($cacheFilePath, json_encode($cacheData));

            $this->logDebug("Filtered CSS updated and cached.");
            return $outFileName;
        }

        $this->logDebug("No CSS rules found for selector: $selector");
        return null;
    }

    /**
     * Formats the raw CSS string for proper indentation.
     *
     * @param string $rawCss The raw CSS content to be formatted.
     *
     * @return string The formatted CSS string.
     */
    private function formatCss(string $rawCss): string
    {
        $rules = array_filter(array_map('trim', explode(';', $rawCss)));
        return implode(";\n    ", $rules) . ';';
    }

    /**
     * Validates the CSS selector, optionally prepending a dot if needed.
     *
     * @param string $selector The selector to validate.
     * @param bool $prependDot Whether to prepend a dot if the selector is not valid.
     *
     * @return string The validated selector.
     */
    private function validateSelector(string $selector, bool $prependDot = true): string
    {
        if ($prependDot && !preg_match('/^[.#\[]/', $selector)) {
            return '.' . $selector;
        }
        return $selector;
    }

    /**
     * Logs debug messages if debug mode is enabled.
     *
     * @param string $message The message to log.
     */
    private function logDebug(string $message)
    {
        if ($this->debugMode) {
            error_log("DEBUG: " . $message);
        }
    }
}
