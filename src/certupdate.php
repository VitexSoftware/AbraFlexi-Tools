<?php
/**
 * FlexiBee Tools  - Certificate updater
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2020 Vitex Software
 */


$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

define('EASE_APPNAME', 'FlexiBee Certificate Updater');
define('EASE_LOGGER', 'syslog|console');

if (empty(\Ease\Functions::cfg('FLEXIBEE_URL'))) {
    echo "Please set up FlexiBee client configuration environment: \n\n";
    echo "FLEXIBEE_URL=https://demo.flexibee.eu:5434\n";
    echo "FLEXIBEE_PASSWORD=winstrom\n";
    echo "FLEXIBEE_LOGIN=winstrom\n";
}

$certificateName = parse_url(\Ease\Functions::cfg('FLEXIBEE_URL'), PHP_URL_HOST);

system('certbot ' . $certificateName . ' --noninteractive');

# convert key to PKCS#1 format
system('openssl rsa -in /etc/letsencrypt/live/' . $certificateName . '/privkey.pem -out le-rsaprivkey.pem');

# download DST Root CA X3 certificate from internet
$leRootCA = file_get_contents('https://ssl-tools.net/certificates/dac9024f54d8f6df94935fb1732638ca6ad77c13.pem');

# combine all the certificates into final le-flexibee.pem
$leFlexiBee = $leRootCA . file_get_contents('/etc/letsencrypt/live/' . $certificateName . '/fullchain.pem') . file_get_contents('le-rsaprivkey.pem');

$uploader = new \FlexiPeeHP\Root();
$uploader->addStatusMessage(_('New certificate upload'), $uploader->uploadCertificate($leFlexiBee) ? 'success' : 'error' );

