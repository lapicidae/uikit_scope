<?php

class uikit_scope_logger extends rex_log_file {

	public static $init = false;
	public static $logfile;

	private $debugMode;

	const DEBUG = "DEBUG";
	const INFO = "INFO";
	const WARNING = "WARNING";
	const ERROR = "ERROR";

	public function __construct() {
		$debugConfig = rex::getProperty('debug');
		$this->debugMode = isset($debugConfig['enabled']) && $debugConfig['enabled'] == 1;
	}

	public static function init() {
		if (!self::$init) {
			self::$logfile = new rex_log_file(rex_path::log('uikit_scope.log'), 4000000);
			self::$init = true;
		}
	}

	private function log(string $message, string $level): void {
		if ($this->debugMode || $level !== self::DEBUG) { // Log debug only when debugMode is true
			self::init();

			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
			$callerFrame = null;
			foreach ($backtrace as $frame) {
				if (!isset($frame['class']) || $frame['class'] !== self::class) {
					$callerFrame = $frame;
					break;
				}
			}

			$className = $callerFrame['class'] ?? 'unknown_class';
			$functionName = $callerFrame['function'] ?? 'unknown_function';

			$data = [
				'type' => $level,
				'class' => $className,
				'function' => $functionName,
				'message' => $message,
			];

			self::$logfile->add($data);
		}
	}

	public function logDebug(string $message): void {
		$this->log($message, self::DEBUG);
	}

	public function logInfo(string $message): void {
		$this->log($message, self::INFO);
	}

	public function logWarning(string $message): void {
		$this->log($message, self::WARNING);
	}

	public function logError(string $message): void {
		$this->log($message, self::ERROR);
	}

	public static function getPath()
	{
		return rex_path::log('uikit_scope.log');
	}

	public static function close()
	{
		self::$logfile = null;
		self::$init = false;
	}
}
