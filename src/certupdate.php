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

require \dirname(__DIR__).'/vendor/autoload.php';

\define('EASE_APPNAME', 'AbraFlexi Certificate Updater');
\define('EASE_LOGGER', 'syslog|console');

if (empty(\Ease\Shared::cfg('ABRAFLEXI_URL'))) {
    echo "Please set up AbraFlexi client configuration environment: \n\n";
    echo "ABRAFLEXI_URL=https://demo.abraflexi.eu:5434\n";
    echo "ABRAFLEXI_PASSWORD=winstrom\n";
    echo "ABRAFLEXI_LOGIN=winstrom\n";
}

$certificateName = parse_url(\Ease\Shared::cfg('ABRAFLEXI_URL'), \PHP_URL_HOST);

// convert key to PKCS#1 format (supports both RSA and ECDSA)
system('openssl pkey -in /etc/letsencrypt/live/'.$certificateName.'/privkey.pem -out le-rsaprivkey.pem');

// combine all the certificates into final le-abraflexi.pem (fullchain.pem already contains the full chain)
$leAbraFlexi = file_get_contents('/etc/letsencrypt/live/'.$certificateName.'/fullchain.pem').file_get_contents('le-rsaprivkey.pem');

$uploader = new \AbraFlexi\Root();
$uploader->addStatusMessage(_('New certificate upload'), $uploader->uploadCertificate($leAbraFlexi) ? 'success' : 'error');
