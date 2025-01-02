<?php

declare(strict_types=1);

/**
 * This file is part of the Tools4AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-Tools
 *
 * (C) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use AbraFlexi\Company;
use AbraFlexi\Functions;
use Ease\Shared;

/**
 * This file is part of the Tools4AbraFlexi package.
 *
 * https://github.com/VitexSoftware/AbraFlexi-Tools
 *
 * (C) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require '../vendor/autoload.php';

\define('EASE_APPNAME', 'AbraFlexi Company Copy');
\define('EASE_LOGGER', 'syslog|console');

if (substr($argv[1], 0, 4) !== 'http') {
    echo 'usage: '.$argv[0]." https://user:password@abraflexi.source.cz:5434/c/firma_a_s_  https://user:password@abraflexi.source.cz:5434/c/firma_a_s_ [production] \n";
    echo "you can also set ABRAFLEXI_URL and ABRAFLEXI_LOGIN,ABRAFLEXI_PASSWORD env variables and specify  only destination URL\n";
    echo '       '.$argv[0]." destination_url [production] \n";
} else {
    if (\array_key_exists(2, $argv) && substr($argv[2], 0, 4) === 'http') {
        $srcOptions = Functions::companyUrlToOptions($argv[1]);
        $dstOptions = Functions::companyUrlToOptions($argv[2]);
        $production = \array_key_exists(3, $argv) && ($argv[3] === 'production');
    } else {
        Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');
        $production = \array_key_exists(2, $argv) && ($argv[2] === 'production');
        $srcOptions = ['company' => Shared::cfg('ABRAFLEXI_COMPANY')]; // Use ENV
        $dstOptions = Functions::companyUrlToOptions($argv[1]);
    }

    $source = new Company($srcOptions['company'], $srcOptions);
    $originalName = null;

    if ($source->lastResponseCode === 200) {
        $backupFile = Shared::cfg('BACKUP_DIRECTORY', sys_get_temp_dir().\DIRECTORY_SEPARATOR).$srcOptions['company'].date('Y-m-d_h:m:s').'.winstorm-backup';
        $source->addStatusMessage(sprintf(_('saving backup to %s'), $backupFile), 'info');

        if ($source->saveBackupTo($backupFile)) {
            $source->addStatusMessage(sprintf(_('backup %s saved'), $backupFile), 'success');
            $dstOptions['ignore404'] = true;
            $target = new Company(
                $dstOptions['company'],
                $dstOptions,
            );

            if (!empty($target->getDataValue('stavEnum'))) {
                $target->addStatusMessage(_('Removing previous company data'), 'info');
            }

            if ($target->deleteFromAbraFlexi() || ($target->lastResponseCode === 404)) {
                if ($target->lastResponseCode === 201) {
                    $target->addStatusMessage(_('company removed before restore'), 'info');
                }

                $target->addStatusMessage(
                    ($production ? _('Production') : _('Development')).' '._('restore begin'),
                    'info',
                );

                if (
                    $target->restoreBackupFrom(
                        $backupFile,
                        $originalName,
                        !$production,
                        !$production,
                        !$production,
                    )
                ) {
                    $target->addStatusMessage(_('backup restored'), 'success');
                } else {
                    $target->addStatusMessage(sprintf(
                        _('company %s was not restored'),
                        $dstOptions['company'],
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
