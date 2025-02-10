<?php

/**
 * UIkit Scope Addon Configuration Page
 * 
 * This script handles the configuration of the UIkit Scope addon within REDAXO.
 */

$addonID = 'uikit_scope';
$addon = rex_addon::get($addonID);

/**
 * Adds an input field for resource paths.
 *
 * @param rex_config_form $form The form object.
 * @param rex_addon $addon The addon object.
 * @param string $name The input field name.
 * @param string $label The language key for the field label.
 * @param string $placeholder The placeholder text.
 * @param string $listID The datalist ID for autocomplete suggestions.
 * @param string|false $validate Optional mark for validation script.
 * @return rex_form_element The created input field.
 */
function addResourceField($form, $addon, $name, $label, $placeholder, $listID, $validate = 'false') {
	$field = $form->addInputField('text', $name, null, ['class' => 'form-control']);
	$field->setLabel($addon->i18n($label));
	$field->setAttribute('placeholder', $placeholder);
	$field->setAttribute('data-id', "source-resource-$name");
	$field->setAttribute('list', $listID);
	$field->setAttribute('data-resource-validate', $validate);
	return $field;
}

/**
 * Generates a datalist for UIkit resource selection.
 *
 * @param string $listID The datalist ID.
 * @param string $UIkitVersion The UIkit version.
 * @param string $fileType The file path/type.
 * @param string|null $comment Optional comment for the entry.
 * @return string The generated HTML datalist.
 */
function addDataList($listID, $UIkitVersion, $fileType, $comment = null) {
	$commentText = $comment ? ' ' . $comment : '';
	return '<datalist id="' . $listID . '">
		<option value="https://cdnjs.cloudflare.com/ajax/libs/uikit/' . $UIkitVersion . '/' . $fileType . '">cdnjs:' . $UIkitVersion . $commentText . '</option>
		<option value="https://cdn.jsdelivr.net/npm/uikit@latest/dist/' . $fileType . '">jsDelivr:latest' . $commentText . '</option>
		<option value="https://cdn.statically.io/gh/uikit/uikit/main/dist/' . $fileType . '">Statically[GitHub]:latest' . $commentText . '</option>
	</datalist>';
}

// Fetch UIkit version with fallback option
$scopeManager = new UIkitScopeManager(true);
$url = 'https://api.cdnjs.com/libraries/uikit?fields=version';
// $url = 'https://unpkg.com/uikit/package.json'; // alternative URL
$fallback = '3.22.0';
$UIkitVersion = $scopeManager->fetchVersion($url, $addonID, $fallback);

// Initialize the configuration form
$form = rex_config_form::factory($addonID);
$form->addFieldset($addon->i18n('config'));

/**
 * Source selection field (included or user-defined resources).
 */
$field = $form->addRadioField('source');
$field->setLabel($addon->i18n('conf_source'));
$field->addOption($addon->i18n('conf_source_included'), 'included');
$field->addOption($addon->i18n('conf_source_included_rtl'), 'included_rtl');
$field->addOption($addon->i18n('conf_source_user'), 'user');

if (!$addon->getConfig('source', false)) {
	$field->setValue('included');
}
$field->setNotice($addon->i18n('conf_source_notice'));

$form->addFieldset($addon->i18n('conf_rsc'));

// Add description & alert field
$form->addRawField('
	<dl class="rex-form-group form-group">
		<dt></dt>
		<dd>
			<p>' . $addon->i18n('uikit_scope_conf_rsc_descr') . '</p>
			<p id="resource-alert" hidden></p>
		</dd>
	</dl>
');

// Add resource input fields
$field = addResourceField($form, $addon, 'rsc_css', 'conf_rsc_css', 'uikit.css / uikit.min.css', 'css-list', 'true');
$field->setNotice(rex_i18n::rawMsg('uikit_scope_conf_rsc_css_notice', 'https://getuikit.com/docs/avoiding-conflicts#scope-mode'));
addResourceField($form, $addon, 'rsc_js', 'conf_rsc_js', 'uikit.js / uikit.min.js', 'js-list', 'true');
addResourceField($form, $addon, 'rsc_icons', 'conf_rsc_icons', 'uikit-icons.js / uikit-icons.min.js', 'icons-list', 'true');

// Add datalists for predefined resource URLs
$form->addRawField(addDataList('css-list', $UIkitVersion, 'css/uikit.min.css', $addon->i18n('conf_rsc_css_noscope')));
$form->addRawField(addDataList('js-list', $UIkitVersion, 'js/uikit.min.js'));
$form->addRawField(addDataList('icons-list', $UIkitVersion, 'js/uikit-icons.min.js'));

// Resource validation button
$form->addRawField('
	<dl class="rex-form-group form-group">
		<dt></dt>
		<dd>
			<button type="button" id="checkResources" class="btn btn-primary">' . $addon->i18n('conf_rsc_button_validate') . '</button>
		</dd>
	</dl>
');

// Checkbox for input correction
//$form->addRawField('<input type="hidden" name="' . mb_strtolower($addon->i18n('conf_rsc')) . '[correction][corr_on]" data-id="source-resource-corr_hdn" value="corr_off" />');
$field = $form->addCheckboxField('correction');
$field->setLabel($addon->i18n('conf_source_correction'));
$field->addOption($addon->i18n('conf_source_correction_on'), 'corr_on');
$field->setAttribute('data-id', "source-resource-correction");

if (!str_contains($addon->getConfig('correction', false), 'corr_off')) {
	$field->setAttribute('checked', 'checked');
}

$field->setNotice($addon->i18n('conf_source_correction_notice'));

// Display session-based messages
$succMessage = rex_session('uikit_scope_success_message');
$infoMessages = rex_session('uikit_scope_info_messages', 'array', []);

if (!empty($succMessage)) {
	echo rex_view::success($succMessage);
	rex_unset_session('uikit_scope_success_message');
}

if (!empty($infoMessages)) {
	echo rex_view::info(implode("<br>", $infoMessages));
	rex_unset_session('uikit_scope_info_messages');
}

// Render the configuration form
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('title'));
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');

/**
 * Handles form submission and resource path validation.
 */
$form_name = $form->getName();

if (rex_post($form_name . '_save')) {

	// Clear cache directories
	foreach ([rex_path::addonAssets($addonID . '/css/generated'), rex_path::addonCache($addonID)] as $dir) {
		if (is_dir($dir)) {
			rex_dir::deleteFiles($dir, true);
		}
	}

	// Input correction
	$infoMessages = [];

	$inCorrect = false;
	
	if ("user" == $addon->getConfig('source') && empty($addon->getConfig('correction'))) {
	 	$addon->setConfig('correction', 'corr_off');
	} else {
		$addon->setConfig('correction', 'corr_on');
	}

	if (str_contains($addon->getConfig('correction'), 'corr_on')) {
		foreach ($addon->getConfig() as $key => $val) {
			if (str_starts_with($key, 'rsc_') && !empty($val)) {
				$originalVal = $val;
				$val = ltrim($val, '/');

				if (preg_match("#^(?:rex_url::assets\('(.+)'\)|assets/(.+))$#", $val, $matches)) {
					$val = !empty($matches[1]) ? $matches[1] : $matches[2];
				}

				if ($originalVal !== $val) {
					$addon->setConfig($key, $val);
					$infoMessages[] = rex_i18n::rawMsg('uikit_scope_conf_source_correction_info', $originalVal, $val);
				}
			}
		}
	}

	// Store success and info messages in session
	rex_set_session('uikit_scope_success_message', rex_i18n::msg('form_applied'));
	rex_set_session('uikit_scope_info_messages', $infoMessages);

	// Redirect to refresh the page
	rex_response::sendRedirect(rex_url::backendPage(rex_be_controller::getCurrentPage()));
}

?>

<script>
$(document).on('rex:ready', function() {
	const rscChecker = new ResourceChecker({
		msgTitleWarn: "<?= $addon->i18n('conf_rsc_msg_title_warning') ?>",
		msgTitleErr: "<?= $addon->i18n('conf_rsc_msg_title_error') ?>",
		msgTitleSucc: "<?= $addon->i18n('conf_rsc_msg_title_success') ?>",
		msgEmpty: "<?= $addon->i18n('conf_rsc_msg_warning_empty') ?>",
		msgSuccess: "<?= $addon->i18n('conf_rsc_msg_success') ?>",
		apiSourcePage: "<?= rex_be_controller::getCurrentPage() ?>",
		debugMode: <?= json_encode(rex::getProperty('debug')['enabled']) ?>
	});
});
</script>
