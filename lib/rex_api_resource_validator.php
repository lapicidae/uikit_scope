<?php

/**
 * Class rex_api_resource_validator
 *
 * Validates a given resource URL or asset path and checks its availability.
 * All non-external inputs are interpreted as asset paths relative to the assets directory.
 */
class rex_api_resource_validator extends rex_api_function {

	/**
	 * @var bool Indicates whether the API function is published.
	 */
	protected $published = false;

	/**
	 * @var uikit_scope_logger Logger instance for debugging and error logging.
	 */
	private $logger;

	/**
	 * @var bool Flag to indicate if debug mode is enabled.
	 */
	private $debugMode;

	/**
	 * Constructor.
	 *
	 * Initializes the debug mode and logger.
	 */
	public function __construct() {
		parent::__construct();
		$this->debugMode = rex::getProperty('debug')['enabled'] ?? false;
		$this->logger    = new uikit_scope_logger();
	}

	/**
	 * Executes the API call.
	 *
	 * Sends a JSON response containing the result of the resource validation.
	 *
	 * @return void
	 */
	public function execute() {
		try {
			rex_response::sendJson($this->validateResource());
		} catch (Exception $e) {
			$this->logger->logError("API Error: " . $e->getMessage());
			rex_response::sendJson([
				'status'  => 'error',
				'message' => 'An error occurred. Please check the logs.',
				'debug'   => $this->debugMode
			], 500);
		}
		exit;
	}

	/**
	 * Validates the provided resource.
	 *
	 * If the resource is a valid external URL, it is used directly.
	 * Otherwise, the input is interpreted as an asset path relative to the assets directory.
	 *
	 * @return array The result of the resource validation.
	 */
	private function validateResource(): array {
		$input = trim(urldecode(rex_request('rsc', 'string', '')));
		if (!$input) {
			return $this->logAndRespond("No resource provided");
		}
		$this->logger->logDebug("Validating resource: $input");

		$resourceType = $this->determineResourceType($input);
		$this->logger->logDebug("Determined resource type: $resourceType for input: $input");

		if ($resourceType === 'external') {
			$resource = $input;
		} else {
			$resource = $this->resolveAssetPath($input);
		}

		return $resourceType === 'external'
			? $this->checkExternalResource($resource)
			: $this->checkAssetResource($resource);
	}

	/**
	 * Determines the type of the resource.
	 *
	 * @param string $resource The input resource string.
	 * @return string Returns 'external' if the input is a valid URL; otherwise, 'asset'.
	 */
	private function determineResourceType(string $resource): string {
		return filter_var($resource, FILTER_VALIDATE_URL) ? 'external' : 'asset';
	}

	/**
	 * Resolves the asset path based on the provided input.
	 *
	 * If the input is in the format "/assets/...", "assets/...", or rex_url::assets('...'),
	 * the relative asset path is extracted. Otherwise, the input is assumed to be relative
	 * to the assets folder.
	 *
	 * @param string $input The raw input string.
	 * @return string The resolved absolute asset path.
	 */
	private function resolveAssetPath(string $input): string {
		if (preg_match("#^(?:/)?assets/(.*)|rex_url::assets\\(['\"]([^'\"]+)['\"]\\)#", $input, $matches)) {
			$assetPath = !empty($matches[1]) ? $matches[1] : ($matches[2] ?? $input);
		} else {
			$assetPath = $input;
		}
		$path = rex_path::assets($assetPath);
		$this->logger->logDebug("Resolved asset path: $path from input: $input");
		return $path;
	}

	/**
	 * Checks if the asset resource exists.
	 *
	 * Logs a debug message indicating whether the asset exists or not.
	 *
	 * @param string $resource The resolved asset path.
	 * @return array An array containing the availability, a message, and debug information.
	 */
	private function checkAssetResource(string $resource): array {
		$exists = file_exists($resource);
		$this->logger->logDebug("Asset " . ($exists ? "exists" : "does NOT exist") . " at path: $resource");
		return [
			"available" => $exists,
			"message"   => $exists ? "Asset exists" : "Asset does NOT exist",
			"debug"     => ["file_path" => $resource, "debug_mode" => $this->debugMode]
		];
	}

	/**
	 * Checks an external resource to see if it is reachable.
	 *
	 * Logs the result of the check.
	 *
	 * @param string $resource The external URL.
	 * @return array An array containing availability, HTTP code, a message, and debug information.
	 */
	private function checkExternalResource(string $resource): array {
		$httpCode  = $this->getHttpStatusCode($resource);
		$available = ($httpCode >= 200 && $httpCode < 400);
		$this->logger->logDebug("External resource " . ($available ? "is reachable" : "is NOT reachable") . " (HTTP code: $httpCode) for URL: $resource");
		return [
			"available" => $available,
			"http_code" => $httpCode,
			"message"   => $available ? "External resource is reachable" : "External resource is NOT reachable",
			"debug"     => ["http_code" => $httpCode, "debug_mode" => $this->debugMode]
		];
	}

	/**
	 * Retrieves the HTTP status code for the given URL.
	 *
	 * It first attempts to use cURL and falls back to get_headers if cURL is not available.
	 *
	 * @param string $url The external URL.
	 * @return int The HTTP status code, or 0 if not retrievable.
	 */
	private function getHttpStatusCode(string $url): int {
		if (function_exists('curl_version')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_NOBODY         => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_TIMEOUT        => 5
			]);
			curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($httpCode) {
				return $httpCode;
			}
		}
		$headers = get_headers($url, 1);
		if (is_array($headers) && isset($headers[0]) && preg_match('/HTTP\\/\\S+\\s+(\\d+)/', $headers[0], $matches)) {
			return (int)$matches[1];
		}
		return 0;
	}

	/**
	 * Logs a warning message and returns a standardized error response.
	 *
	 * @param string $message The warning message.
	 * @param bool   $status  The availability status.
	 * @return array An array containing the availability, a message, and debug information.
	 */
	private function logAndRespond(string $message, bool $status = false): array {
		$this->logger->logWarning($message);
		return [
			"available" => $status,
			"message"   => $message,
			"debug"     => $this->debugMode ? ["debug_mode" => true] : []
		];
	}
}
