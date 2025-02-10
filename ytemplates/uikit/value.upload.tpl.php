<?php

/**
 * @var rex_yform_value_upload $this
 *
 * @psalm-scope-this rex_yform_value_upload
*/

$unique ??= '';
$filename ??= '';
$download_link ??= '';
$error_messages ??= [];
$configuration ??= [];
$allowed_extensions = $configuration['allowed_extensions'] ?? ['*'];
$allowed_extensions = '*' == $allowed_extensions[0] ? '*' : '.' . implode(',.', $configuration['allowed_extensions']);

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notice = [];
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notice[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</div>'; //	var_dump();
}
if ('' != $this->getElement('notice')) {
	$notice[] = '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}
if (count($notice) > 0) {
	$notice = '<div class="uk-flex uk-flex-wrap uk-text-meta" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notice) . '</div>';
} else {
	$notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('uk-margin  ' . $class . $warningClass);
$class_control = 'uk-form-controls';

$inputAttributes = [
	// 'class' => $class_control,
	'id' => $this->getFieldId(),
	'type' => 'file',
	'name' => $unique,
	'accept' => $allowed_extensions,
	'aria-label' => rex_i18n::msg('uikit_scope_choose_file'),
];
$inputAttributes = $this->getAttributeElements($inputAttributes, ['required', 'disabled', 'readonly']);

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
	<?php if ($this->getLabel() && preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1): ?>
	<label class="uk-form-label" for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
	<?php endif ?>
	<div class="<?= $class_control ?>">
		<div uk-form-custom="target: true">
			<input <?= implode(' ', $inputAttributes) ?>>
			<input class="uk-input uk-form-width-medium" type="text" placeholder="<?= rex_i18n::msg('uikit_scope_choose_file') ?>" aria-label="<?= rex_i18n::msg('uikit_scope_choose_file') ?>" disabled>
		</div>
		<button class="uk-button uk-button-default" type="button" onclick="resetFileInput('<?= $this->getHTMLId() ?>');" aria-label="<?= rex_i18n::msg('form_delete') ?>"><span uk-icon="trash"></span></button>
		<?= $notice ?>
	</div>
	<input type="hidden" name="<?= $this->getFieldName('unique') ?>" value="<?= rex_escape($unique, 'html') ?>" />
</div>

<?php
	if ('' != $filename) {
		$label = rex_escape($filename);

		if (rex::isBackend() && '' != $download_link) {
			$label = '<a href="' . $download_link . '">' . $label . '</a>';
		}

		echo '
			<div class="checkbox" id="' . $this->getHTMLId('checkbox') . '">
				<label>
					<input type="checkbox" id="' . $this->getFieldId('delete') . '" name="' . $this->getFieldName('delete') . '" value="1" />
					' . ($error_messages['delete_file'] ?? 'delete-file-msg') . ' "' . $label . '"
				</label>
			</div>';
	}
?>

<script nonce="<?= rex_response::getNonce() ?>">
	function resetFileInput(id) {
		const el = document.getElementById(id);
		if (!el) return;

		// create a temporary form
		const form = document.createElement('form');
		el.parentNode.insertBefore(form, el);
		form.appendChild(el);

		// resetting the form
		form.reset();

		// detach the element from the form
		form.parentNode.insertBefore(el, form);
		form.remove();
	}
</script>
