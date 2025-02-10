<?php

/**
 * @var rex_yform_value_text $this
 * @psalm-scope-this rex_yform_value_text
*/

$type ??= 'text';
//$class = 'uk-input' == $type ? '' : 'uk-input-' . $type . ' ';
if (!isset($value)) {
	$value = $this->getValue();
}

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notice = [];
$class_notice = 'uk-flex uk-flex-wrap uk-text-meta';
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notice[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()]) . '</div>'; //	var_dump();
}
if ('' != $this->getElement('notice')) {
	$notice[] = '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}
if (count($notice) > 0) {
	$notice = '<div class="' . $class_notice . '" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notice) . '</div>';
} else {
	$notice = '';
}

$class_group = [];
$class_group['form-group'] = 'uk-margin';

$class_label[] = 'uk-form-label';

$attributes = [
	'class' => 'uk-input',
	'name' => $this->getFieldName(),
	'type' => $type,
	'id' => $this->getFieldId(),
	'value' => $value,
	'aria-label' => $this->getLabel(),
];

if (!empty($this->getWarningClass())) {
	$attributes['class'] .= ' ' . $warningClass;
}

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

$input_group_start = '';
$input_group_end = '';

$prepend_view = '';
$append_view = '';

if (preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1) {

	if (!empty($notice)) {
		$notice = str_replace($class_notice, $class_notice . ' uk-form-controls', $notice);
	}

	if (!empty($prepend) || !empty($append)) {
		if (preg_match('/uk-form-horizontal/', $this->params['this']->getObjectparams('form_class')) === 1) {
			$input_group_start = '<div class="uk-form-controls uk-grid-small uk-flex-middle" uk-grid="first-column: uk-padding-remove-left">';
		} else {
			$input_group_start = '<div class="uk-form-controls uk-grid-small uk-flex-middle" uk-grid>';
		}
		$input_group_end = '</div>';
		$class_inputdiv = 'uk-width-expand';
	}

	if (!empty($prepend)) {
		$prepend_view = '<span>' . $prepend . '</span>';
	}

	if (!empty($append)) {
		$append_view = '<span>' . $append . '</span>';
	}

	if (empty($prepend) && empty($append)) {
		$class_inputdiv = 'uk-form-controls';
	}

	echo '<div class="' . implode(' ', $class_group) . '" id="' . $this->getHTMLId() . '">
		<label class="' . implode(' ', $class_label) . '" for="' . $this->getFieldId() . '">' . $this->getLabel() . '</label>
		' . $input_group_start . $prepend_view . '<div class="' . $class_inputdiv . '"><input ' . implode(' ', $attributes) . ' /></div>' . $append_view . $input_group_end . $notice . '
		</div>';

} else {

	if (!preg_grep('/^placeholder=/', $attributes)) {
		$attributes[] = trim('placeholder=' . $this->getLabel());
	}

	if (!empty($prepend) || !empty($append)) {
		$input_group_start = '<div class="uk-flex uk-flex-middle">';
		$input_group_end = '</div>';
	}
	if (!empty($prepend)) {
		$prepend_view = '<span class="uk-margin-small-right">' . $prepend . '</span>';
	}

	if (!empty($append)) {
		$append_view = '<span class="uk-margin-small-left">' . $append . '</span>';
	}

	echo '<div class="' . implode(' ', $class_group) . '" id="' . $this->getHTMLId() . '">
			' . $input_group_start . $prepend_view . '
			<input ' . implode(' ', $attributes) . '>
			' . $append_view . $input_group_end . $notice . '
			</div>';

}
?>
<?php
// REDAXO uses its own code for the ‘Password eye’ in the backend
// therefore we use a workaround to remove the native Redaxo addition
?>
<?php if (preg_grep('/^type="password"/', $attributes)): ?>

<script type="module" nonce="<?= rex_response::getNonce() ?>">
	if (!window.passwordEnhancerInitialized) {
    window.passwordEnhancerInitialized = true;

    document.addEventListener('DOMContentLoaded', function () {
        jQuery(document).ready(function ($) {

            // Check if an element or any of its parents contains "uk-" in its CSS class
            function hasUkClass(element) {
                return $(element).closest('[class*="uk-"]').length > 0;
            }

			<?php if (rex::isBackend()): ?>
            // Tidying up password fields
            function resetPasswordInputs() {
                $('input[type="password"]').each(function () {
                    const $el = $(this);

                    // Skip if the element or its parents do not contain a "uk-" class
                    if (!hasUkClass($el)) {
                        return;
                    }

                    // Remove additional wrappers and buttons
                    const $wrapper = $el.closest('div.input-group');
                    if ($wrapper.length) {
                        $wrapper.find('span.input-group-btn').remove();
                        $el.unwrap();
                    }

                    // Make sure that the type is set to ‘password’
                    $el.attr('type', 'password');
                });
            }

            resetPasswordInputs(); // Remove existing buttons/wrappers
            <?php endif ?>

            // Add UIkit components for password fields
            function enhancePasswordInputs() {
                const tooltipDelay = 250; // Tooltip delay in milliseconds
                const tooltipShow = '<?= rex_i18n::msg('uikit_scope_show_password') ?>'; // Tooltip 'Show password'
                const tooltipHide = '<?= rex_i18n::msg('uikit_scope_hide_password') ?>'; // Tooltip 'Hide password'

                $('input[type="password"]').each(function () {
                    const $passwordInput = $(this);

                    // Skip if the element or its parents do not contain a "uk-" class
                    if (!hasUkClass($passwordInput)) {
                        return;
                    }

                    const $parent = $passwordInput.parent();

                    if (!$parent.hasClass('uk-position-relative')) {
                        $parent.addClass('uk-position-relative');
                    }

                    const $button = $('<a>')
                        .addClass('uk-form-icon uk-form-icon-flip')
                        .attr('uk-icon', 'icon: eye')
                        .attr('uk-tooltip', `title: ${tooltipShow}; delay: ${tooltipDelay}`);
                    $passwordInput.before($button);

                    $button.on('click', function () {
                        const isPassword = $passwordInput.attr('type') === 'password';
                        $passwordInput.attr('type', isPassword ? 'text' : 'password');
                        $button
                            .attr('uk-icon', isPassword ? 'icon: eye-slash' : 'icon: eye')
                            .attr(
                                'uk-tooltip',
                                `title: ${isPassword ? tooltipHide : tooltipShow}; delay: ${tooltipDelay}`
                            );
                        $passwordInput.focus();
                    });
                });
            }

            enhancePasswordInputs(); // Add new buttons/wrappers
        });
    });
}
</script>

<?php endif ?>
