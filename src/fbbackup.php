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

require \dirname(__DIR__).'/vendor/autoload.php';

\define('EASE_APPNAME', 'AbraFlexi Backup Download');
\define('EASE_LOGGER', 'syslog|console');

if (\array_key_exists(1, $argv) && $argv[1] === '-h') {
    echo 'usage: '.$argv[0]." https://user:password@abraflexi.source.cz:5434/c/firma_a_s_ \n";
    echo "you can also set ABRAFLEXI_URL and ABRAFLEXI_LOGIN,ABRAFLEXI_PASSWORD env variables and specify source credentials\n";
    echo '       '.$argv[0]." destination_url \n";
} else {
    if (\array_key_exists(1, $argv) && substr($argv[1], 0, 4) === 'http') {
        $srcOptions = Functions::companyUrlToOptions($argv[1]);
    } else {
        Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');
        $srcOptions = ['company' => Shared::cfg('ABRAFLEXI_COMPANY')]; // Use ENV
    }

    $source = new Company($srcOptions['company'], $srcOptions);

    if ($source->lastResponseCode === 200) {
        $backupFile = Shared::cfg('BACKUP_DIRECTORY', sys_get_temp_dir().\DIRECTORY_SEPARATOR).$srcOptions['company'].date('Y-m-d_h:m:s').'.winstorm-backup';
        $source->addStatusMessage(sprintf(_('saving backup to %s'), $backupFile), 'info');

        if ($source->saveBackupTo($backupFile)) {
            $source->addStatusMessage(sprintf(_('backup %s saved'), $backupFile), 'success');
        } else {
            $source->addStatusMessage(_('error saving backup'), 'error');
        }
    }
}
