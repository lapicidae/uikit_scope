<?php

/**
 * Script to include UIkit CSS and JS files in the REDAXO backend.
 */

$addonID = 'uikit_scope';
$addon = rex_addon::get($addonID);

/**
 * Register yform template path if yform addon is available and the path exists.
 */
if (rex_addon::get('yform', 'manager')->isAvailable() && is_dir($addon->getPath('ytemplates'))) {
	rex_yform::addTemplatePath($addon->getPath('ytemplates'));
}

/**
 * Include UIkit files in the backend.
 */
if (rex::isBackend()) {

	/**
	 * Include UIkit files in the backend content edit page.
	 */
	if ('content/edit' == rex_be_controller::getCurrentPage()) {

		/**
		 * Get UIkit CSS and JS URLs. Prioritize user configuration.
		 */
		$cssUIkit = $addon->getConfig('rsc_css') ?? $addon->getAssetsUrl('css/uikit-scope.min.css');
		$jsUIkit = $addon->getConfig('rsc_js') ?? $addon->getAssetsUrl('js/uikit.min.js');
		$iconsUIkit = $addon->getConfig('rsc_icons') ?? $addon->getAssetsUrl('js/uikit-icons.min.js');

		/**
		 * Apply UIkit scoping workaround
		 * Workaround for: https://github.com/uikit/uikit/issues/4964
		 */
		$scopeManager = new UIkitScopeManager();
		$scopeManager->setSourcePath($cssUIkit);
		$cssNewFile = $scopeManager->filterCSS('html', '.uk-scope');
		if ($cssNewFile) {
			$cssWorkaroundUIkit = $cssNewFile->url;
			rex_view::addCssFile($cssWorkaroundUIkit);
		} else {
			$this->logger->logWarning("Failed to generate scoped CSS.");
		}

		/**
		 * Add CSS files to the backend.
		 */
		rex_view::addCssFile($addon->getAssetsUrl('css/uikit-scope-css-initials.css'));
		rex_view::addCssFile($cssUIkit);

		/**
		 * Add JS files to the backend.
		 */
		$jsFiles = [
			$jsUIkit => [rex_view::JS_IMMUTABLE => true],
			$iconsUIkit => [rex_view::JS_IMMUTABLE => true],
			$addon->getAssetsUrl('js/uikit-scope.js') => [rex_view::JS_IMMUTABLE => true, rex_view::JS_DEFERED => true],
		];

		foreach ($jsFiles as $file => $options) {
			rex_view::addJsFile($file, $options);
		}
	}

	/**
	 * Include configuration JS file in the uikit_scope config page.
	 */
	if ('uikit_scope/config' == rex_be_controller::getCurrentPage()) {
		rex_view::addJsFile($addon->getAssetsUrl('js/uikit-scope-config.js'), [rex_view::JS_IMMUTABLE => true]);
	}
}

?>
