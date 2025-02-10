<?php

$addon = rex_addon::get('uikit_scope');

// default settings
if (!$addon->hasConfig()) {
	$addon->setConfig('source', 'included');
}
