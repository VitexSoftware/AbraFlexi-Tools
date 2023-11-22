<?php

/**
 * AbraFlexi Tools  - AbraFlexi copy
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2020-2023 Vitex Software
 */

$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}


define('EASE_APPNAME', 'AbraFlexi Company Copy');
define('EASE_LOGGER', 'syslog|console');

if (substr($argv[1], 0, 4) != 'http') {
    echo "usage: " . $argv[0] . " https://user:password@abraflexi.source.cz:5434/c/firma_a_s_  https://user:password@abraflexi.source.cz:5434/c/firma_a_s_ [production] \n";
    echo "you can also set ABRAFLEXI_URL and ABRAFLEXI_LOGIN,ABRAFLEXI_PASSWORD env variables and specify  only destination URL\n";
    echo "       " . $argv[0] . " destination_url [production] \n";
} else {
    if (substr($argv[2], 0, 4) == 'http') {
        $srcOptions = \AbraFlexi\RO::companyUrlToOptions($argv[1]);
        $dstOptions = \AbraFlexi\RO::companyUrlToOptions($argv[2]);
        $production = array_key_exists(3, $argv) && ($argv[3] == 'production');
    } else {
        \Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');
        $production = array_key_exists(2, $argv) && ($argv[2] == 'production');
        $srcOptions = ['company' => \Ease\Shared::cfg('ABRAFLEXI_COMPANY')]; //Use ENV
        $dstOptions = \AbraFlexi\RO::companyUrlToOptions($argv[1]);
    }

    $source = new \AbraFlexi\Company($srcOptions['company'], $srcOptions);
    $originalName = null;
    if ($source->lastResponseCode == 200) {
        $backupFile = \Ease\Functions::cfg('BACKUP_DIRECTORY', sys_get_temp_dir() . DIRECTORY_SEPARATOR) . $srcOptions['company'] . date('Y-m-d_h:m:s') . '.winstorm-backup';
        $source->addStatusMessage(_('saving backup'), 'info');
        if ($source->saveBackupTo($backupFile)) {
            $source->addStatusMessage(sprintf(_('backup %s saved'), $backupFile), 'success');
            $dstOptions['ignore404'] = true;
            $target = new \AbraFlexi\Company(
                $dstOptions['company'],
                $dstOptions
            );
            if (!empty($target->getDataValue('stavEnum'))) {
                $target->addStatusMessage(_('Removing previous company data'), 'info');
            }
            if ($target->deleteFromAbraFlexi() || ($target->lastResponseCode == 404)) {
                if ($target->lastResponseCode == 201) {
                    $target->addStatusMessage(_('company removed before restore'), 'info');
                }
                $target->addStatusMessage(
                    ($production ? _('Production') : _('Development')) . ' ' . _('restore begin'),
                    'info'
                );
                if (
                        $target->restoreBackupFrom(
                            $backupFile,
                            $originalName,
                            !$production,
                            !$production,
                            !$production
                        )
                ) {
                    $target->addStatusMessage(_('backup restored'), 'success');
                } else {
                    $target->addStatusMessage(sprintf(
                        _('company %s was not restored'),
                        $dstOptions['company']
                    ), 'warning');
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
