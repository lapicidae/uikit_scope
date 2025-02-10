<?php

$addon = rex_addon::get('uikit_scope');

echo rex_view::title($addon->i18n('title'));

rex_be_controller::includeCurrentPageSubPath();
