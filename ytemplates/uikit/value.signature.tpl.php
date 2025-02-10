<?php

/**
 * @var rex_yform_value_signature $this
 *
 * @psalm-scope-this rex_yform_value_signature
*/

$value ??= $this->getValue();

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notice = [];
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notice[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</div>'; //	var_dump();
}
if ('' != $this->getElement('notice')) {
	$notice[] = '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}
if (count($notice) > 0) {
	$notice = '<div class="uk-form-controls uk-flex uk-flex-wrap uk-text-meta" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notice) . '</div>';
} else {
	$notice = '';
}

$class_group = trim('uk-margin ' . $warningClass);
$class_label[] = 'uk-form-label';

$field_before = '';
$field_after = '';
$specialAttributes = $this->getAttributeArray([]);

$attributes = [
	'class' => 'uk-form-controls',
	'name' => $this->getFieldName(),
	'type' => 'hidden',
	'id' => 'canvas-target-' . $this->getName(),
	'value' => $value,
];

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
	<?php if ($this->getLabel() && preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1): ?>
	<label class="<?= implode(' ', $class_label) ?>"><?= $this->getLabel() ?></label>
	<?php endif ?>
	<div class="uk-form-controls">
		<div class="uk-inline uk-textarea uk-padding-remove uk-width-auto">
			<canvas class="uk-width-medium uk-height-small" id="canvas-<?= $this->getName() ?>"></canvas>
			<?php if (isset($value) && '' != $value) { ?>
				<img src="<?= $value ?>">
			<?php } ?>
			<div class="uk-position-small uk-position-top-right">
				<button type="button" class="uk-icon-link" id="clear-<?= $this->getName() ?>" onclick="eraseSignature_<?= $this->getName() ?>()" uk-icon="trash" aria-label="<?= rex_i18n::msg('form_delete') ?>"></button>
				<!-- <button type="button" class="uk-icon-button" id="clear-<?= $this->getName() ?>" onclick="eraseSignature_<?= $this->getName() ?>()" uk-icon="trash" aria-label="<?= rex_i18n::msg('form_delete') ?>"></button> -->
				<!-- <button type="button" class="uk-button uk-button-default uk-button-small" id="clear-<?= $this->getName() ?>" onclick="eraseSignature_<?= $this->getName() ?>()" aria-label="<?= rex_i18n::msg('form_delete') ?>"><span uk-icon="trash"></span></button> -->
			</div>
		</div>
		<input <?= implode(' ', $attributes) ?>>
	</div>
	<?= $notice ?>
</div>

<script nonce="<?= rex_response::getNonce() ?>">
	if (typeof rex !== 'undefined' && rex.backend) {
		$(document).on("rex:ready", function(){
			initSignature_<?= $this->getName() ?>();
		});
	} else {
		document.addEventListener("DOMContentLoaded", function() {
			initSignature_<?= $this->getName() ?>();
		});
	}

	function initSignature_<?= $this->getName() ?>() {
    let base_id = '<?= $this->getName() ?>',
        $canvas = $("#canvas-" + base_id),
        $target = $("#canvas-target-" + base_id),
        ctx,
        flag = false,
        dot_flag = false,
        prevX = 0,
        currX = 0,
        prevY = 0,
        currY = 0,
        x = "black",
        y = 2;

    ctx = $canvas[0].getContext("2d");
    let w = $canvas[0].width = $canvas[0].offsetWidth;
    let h = $canvas[0].height = $canvas[0].offsetHeight;

    $canvas.on("mousedown touchstart", handleStart);
    $canvas.on("mousemove touchmove", handleMove);
    $canvas.on("mouseup mouseout touchend touchcancel", handleEnd);

    function handleStart(evt) {
        evt.preventDefault();
        flag = true;
        dot_flag = true;

        let eventX, eventY;
        if (evt.originalEvent.touches) {
            let touch = evt.originalEvent.touches[0];
            eventX = touch.clientX;
            eventY = touch.clientY;
        } else {
            eventX = evt.clientX;
            eventY = evt.clientY;
        }

        prevX = currX = eventX;
        prevY = currY = eventY;

        if (dot_flag) {
            ctx.beginPath();
            ctx.fillStyle = x;
            ctx.fillRect(currX, currY, 2, 2);
            ctx.closePath();
            dot_flag = false;
        }
    }

    function handleMove(evt) {
        evt.preventDefault();
        if (flag) {
            prevX = currX;
            prevY = currY;

            let eventX, eventY;
            if (evt.originalEvent.touches) {
                let touch = evt.originalEvent.touches[0];
                eventX = touch.clientX;
                eventY = touch.clientY;
            } else {
                eventX = evt.clientX;
                eventY = evt.clientY;
            }

            currX = eventX;
            currY = eventY;

            draw();
        }
    }

    function handleEnd(evt) {
        evt.preventDefault();
        flag = false;
    }

    function draw() {
        let offset = $canvas[0].getBoundingClientRect();

        ctx.beginPath();
        ctx.moveTo(prevX - offset.left, prevY - offset.top);
        ctx.lineTo(currX - offset.left, currY - offset.top);
        ctx.strokeStyle = x;
        ctx.lineWidth = y;
        ctx.stroke();
        ctx.closePath();

        // insert result into the hidden input field
        $target.val($canvas[0].toDataURL());
    }
}

function eraseSignature_<?= $this->getName() ?>() {
    let m = confirm("<?= rex_i18n::msg('uikit_scope_erase_signature') ?>");
    if (m) {
        let $canvas = $("#canvas-<?= $this->getName() ?>");
        let ctx = $canvas[0].getContext("2d");
        ctx.clearRect(0, 0, $canvas[0].width, $canvas[0].height);
    }
}

</script>
