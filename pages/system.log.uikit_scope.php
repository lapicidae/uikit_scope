<?php
/**
 * This script outputs the log file for the uikit_scope addon.
 * It supports deleting the log file, downloading it, and displaying a limited number of log entries.
 * The number of displayed log entries can be configured in the package.yml (e.g. logfile.listEntries: 300).
 *
 * @package uikit_scope
 */

// Get the addon instance.
$addon = rex_addon::get('uikit_scope');
$error = '';
$success = '';
$func = rex_request('func', 'string');

// Define the log file path.
$logFile = rex_path::log('uikit_scope.log');

/**
 * Delete log file action.
 */
if ('uikit_scope_delLog' === $func) {
    uikit_scope_logger::close();
    if (rex_log_file::delete($logFile)) {
        $success = rex_i18n::msg('syslog_deleted');
    } else {
        $error = rex_i18n::msg('syslog_delete_error');
    }
}

/**
 * Download log file action.
 */
if ('download' === $func && file_exists($logFile)) {
    rex_response::sendFile($logFile, 'application/octet-stream', 'attachment');
    exit;
}

// Build messages to be displayed (success/error).
$message = '';
if ('' !== $success) {
    $message .= rex_view::success($success);
}
if ('' !== $error) {
    $message .= rex_view::error($error);
}

/**
 * Build the HTML table for log file entries.
 * The number of entries is limited by the addon property 'logfile.listEntries' (default: 200).
 */
ob_start();
?>
<table class="table table-hover">
    <thead>
        <tr>
            <th><?= rex_i18n::msg('syslog_timestamp') ?></th>
            <th><?= rex_i18n::msg('syslog_message') ?></th>
        </tr>
    </thead>
    <tbody>
<?php
// Create a log file object.
$file = new rex_log_file($logFile);
$maxEntries = $addon->getProperty('logfile.listEntries', 200);
$iterator = new LimitIterator($file, 0, $maxEntries);

/** @var rex_log_entry $entry */
foreach ($iterator as $entry) {
    $data = $entry->getData();
    $level = isset($data[0]) ? $data[0] : '';
    $class = 'label-default';
    if ('WARNING' === $level) {
        $class = 'label-warning';
    } elseif ('ERROR' === $level) {
        $class = 'label-danger';
    } elseif ('INFO' === $level) {
        $class = 'label-info';
    }
    ?>
        <tr>
            <td data-title="<?= rex_i18n::msg('syslog_timestamp') ?>" class="rex-table-tabular-nums rex-table-date">
                <small><?= $entry->getTimestamp('%d.%m.%Y, %H:%M:%S') ?></small><br>
                <span class="label <?= $class ?>"><?= ucfirst(strtolower($level)) ?></span>
            </td>
            <td data-title="<?= rex_i18n::msg('syslog_message') ?>">
                <div class="rex-word-break">
                    <?php if (isset($data[3])): ?>
                        <b style="font-weight: 500"><?= $data[3] ?></b><br>
                    <?php endif; ?>
                </div>
                <?php if (isset($data[2])): ?>
                    <small class="rex-word-break"><span class="label label-default"><?= $addon->i18n('log_function') ?></span> <?= $data[2] ?></small><br>
                <?php endif; ?>
                <?php if (isset($data[1])): ?>
                    <small class="rex-word-break"><span class="label label-default"><?= $addon->i18n('log_class') ?></span> <?= $data[1] ?></small>
                <?php endif; ?>
            </td>
        </tr>
    <?php
}
?>
    </tbody>
</table>
<?php
$contentTable = ob_get_clean();

/**
 * Build form elements for log file actions (delete, open in editor, download).
 */
$formElements = [];

$n = [];
$n['field'] = '<button class="btn btn-delete" type="submit" name="del_btn" data-confirm="' . rex_i18n::msg('delete') . '?">' . rex_i18n::msg('syslog_delete') . '</button>';
$formElements[] = $n;

if ($url = rex_editor::factory()->getUrl($logFile, 0)) {
    $n = [];
    $n['field'] = '<a class="btn btn-save" href="' . $url . '">' . rex_i18n::msg('system_editor_open_file', basename($logFile)) . '</a>';
    $formElements[] = $n;
}

if (file_exists($logFile) && filesize($logFile)) {
    $url = rex_url::currentBackendPage(['func' => 'download']);
    $n = [];
    $n['field'] = '<a class="btn btn-save" href="' . $url . '">' . rex_i18n::msg('syslog_download', basename($logFile)) . '</a>';
    $formElements[] = $n;
}

/**
 * Parse the form elements using a fragment.
 */
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('syslog_title', $logFile), false);
$fragment->setVar('content', $contentTable, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

/**
 * Wrap the content in a form for the log deletion action.
 */
$content = '<form action="' . rex_url::currentBackendPage() . '" method="post">' .
           '<input type="hidden" name="func" value="uikit_scope_delLog" />' .
           $content .
           '</form>';

// Output the message and content.
echo $message;
echo $content;
?>
