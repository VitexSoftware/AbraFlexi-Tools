<?php

/**
 * AbraFlexi Tools  - AbraFlexi copy
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2020-2022 Vitex Software
 */
$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

define('BACKUP_DIRECTORY', sys_get_temp_dir() . DIRECTORY_SEPARATOR);
define('EASE_APPNAME', 'AbraFlexi Company Transfer');
define('EASE_LOGGER', 'syslog|console');

function urlToOptions($url) {
    $optionsRaw = parse_url($url);
    $options['url'] = $optionsRaw['scheme'] . '://' . $optionsRaw['host'] . ':' . $optionsRaw['port'];
    $options['company'] = str_replace('/c/', '', $optionsRaw['path']);
    $options['user'] = $optionsRaw['user'];
    $options['password'] = $optionsRaw['pass'];
    return $options;
}

if ($argc < 3) {
    echo "usage: " . $argv[0] . " https://user:password@abraflexi.source.cz:5434/c/firma_a_s_  https://user:password@abraflexi.source.cz:5434/c/firma_a_s_ [production] \n";
} else {
    $srcOptions = urlToOptions($argv[1]);
    $production = array_key_exists(3, $argv) && ($argv[3] == 'production');
    $source = new \AbraFlexi\Company($srcOptions['company'], $srcOptions);
    $originalName = null;
    if ($source->lastResponseCode == 200) {

        $backupFile = \Ease\Functions::cfg('BACKUP_DIRECTORY') . $srcOptions['company'] . '.winstorm-backup';
        $source->addStatusMessage(_('saving backup'), 'info');
        if ($source->saveBackupTo($backupFile)) {
            $source->addStatusMessage(sprintf(_('backup %s saved'), $backupFile), 'success');
            $dstOptions = urlToOptions($argv[2]);
            $target = new \AbraFlexi\Company($dstOptions['company'],
                    $dstOptions);
            if (!empty($target->getDataValue('stavEnum'))) {
                $target->addStatusMessage(_('Remove company before restore'), 'info');
            }
            if ($target->deleteFromAbraFlexi() || ($target->lastResponseCode == 404)) {
                if ($target->lastResponseCode == 201) {
                    $target->addStatusMessage(_('company removed before restore'));
                }
                $target->addStatusMessage(($production ? _('Production') : _('Development')) . ' ' . _('restore begin'),
                        'success');
                if ($target->restoreBackupFrom($backupFile, $originalName,
                                !$production, !$production, !$production)) {
                    $target->addStatusMessage(_('backup restored'), 'success');
                } else {
                    $target->addStatusMessage(sprintf(_('company %s was not restored'),
                                    $dstOptions['company']), 'warning');
                }
            } else {
                $target->addStatusMessage(_('company cleanup failed'), 'warning');
            }
            unlink($backupFile);
        } else {
            $source->addStatusMessage(_('error saving backup'), 'error');
        }
    }
}
