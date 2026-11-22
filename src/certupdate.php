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

try {
    $url = \Ease\Shared::cfg('ABRAFLEXI_URL');
    $certificateName = \is_string($url) ? parse_url($url, \PHP_URL_HOST) : null;
    if (!\is_string($certificateName) || !preg_match('/\A[a-zA-Z0-9.-]+\z/', $certificateName)) {
        throw new RuntimeException('Set ABRAFLEXI_URL to a valid server URL.');
    }

    $directory = '/etc/letsencrypt/live/'.$certificateName;
    $chain = file_get_contents($directory.'/fullchain.pem');
    $trustedCertificates = file_get_contents('/etc/ssl/certs/ca-certificates.crt');
    if ($chain === false || $trustedCertificates === false) {
        throw new RuntimeException('Cannot read the certificate chain or system CA bundle.');
    }

    $process = proc_open(
        ['openssl', 'rsa', '-traditional', '-in', $directory.'/privkey.pem'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );
    if (!\is_resource($process)) {
        throw new RuntimeException('Cannot start openssl.');
    }
    fclose($pipes[0]);
    $privateKey = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || $privateKey === false) {
        throw new RuntimeException('Cannot convert the RSA private key: '.$error);
    }

    $bundle = \AbraFlexi\Tools\CertificateBundle::create($chain, $privateKey, $trustedCertificates);
    $uploader = new \AbraFlexi\Root();
    if (!$uploader->uploadCertificate($bundle)) {
        throw new RuntimeException('AbraFlexi rejected the certificate upload.');
    }
    $uploader->addStatusMessage(_('New certificate upload'), 'success');
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
