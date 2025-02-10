<?php
class rex_var_uikit_scope extends rex_var
{
	/**
	 * Generates the output for the REX variable.
	 *
	 * @param bool $quote Whether to wrap the output in quotes. Defaults to true.
	 * @return string The generated HTML output, optionally wrapped in quotes.
	 */
	protected function getOutput($quote = true)
	{
		// Only process in backend mode; return an empty string otherwise.
		if (!rex::isBackend()) {
			return $quote ? self::quote('') : '';
		}

		// Retrieve the "do" parameter (Redaxo sets this automatically based on the tag).
		$order = strtolower($this->getArg('do', null, true));
		$customId = $this->getArg('id', null, false); // Retrieve the optional "id" parameter
		$uniqueId = 'uk-scope-' . uniqid(); // Default ID, will be used if no custom ID is set.
		$comment = '<!-- REX_UIKIT_SCOPE[' . $order . '] -->';

		// If "do=start" is detected, use the custom ID if provided.
		if ($order === 'start' && $customId) {
			$uniqueId = $customId;
			$comment = sprintf(
				'<!-- REX_UIKIT_SCOPE[do=%1$s id=%2$s] -->',
				$order,
				$uniqueId
			);
		}

		// Default output if the parameter is missing or invalid.
		$output = '<!-- Incorrect use of REX_UIKIT_SCOPE[]! Use either start, begin, stop, end, or exit as an argument. Example: REX_UIKIT_SCOPE[start] -->';

		if ($order) {
			// Define allowed arguments for opening and closing the scope.
			$startArgs = ['start', 'begin'];
			$endArgs   = ['stop', 'end', 'exit'];

			// If the input matches a start argument, generate the opening tag.
			if (in_array($order, $startArgs, true)) {
				$output = sprintf(
					'%1$s%2$s<div id="%3$s" class="uk-scope">',
					$comment,
					PHP_EOL,
					$uniqueId
				);
			}
			// If the input matches an end argument, generate the closing tag.
			elseif (in_array($order, $endArgs, true)) {
				$output = sprintf(
					'</div>%2$s%1$s%2$s',
					$comment,
					PHP_EOL
				);
			}
		}

		// Return the output, optionally wrapped in quotes to ensure proper processing by Redaxo.
		return $quote ? self::quote($output) : $output;
	}

	/**
	 * Returns the generated output for a given input parameter.
	 *
	 * @param string $order The input parameter (allowed values: start, begin, stop, end, exit).
	 * @param string|null $id Optional: The custom ID for the start scope.
	 * @param bool $quote Whether to wrap the output in quotes. Defaults to true.
	 * @return string The generated HTML output.
	 */
	public static function scope($order, $id = null, $quote = false)
	{
		// Create an instance of the class.
		$instance = new self();

		// Use Reflection to set the private "args" property of the parent class.
		// This ensures that getArg() retrieves the input value correctly.
		$refClass = new ReflectionClass('rex_var');
		$prop = $refClass->getProperty('args');
		$prop->setAccessible(true);
		$prop->setValue($instance, ['do' => $order, 'id' => $id]);

		// Return the processed output, passing the $quote parameter to getOutput().
		return $instance->getOutput($quote);
	}
}
