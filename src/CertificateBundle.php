<?php

declare(strict_types=1);

namespace AbraFlexi\Tools;

use RuntimeException;

final class CertificateBundle
{
    public static function create(string $chain, string $privateKey, string $trustedCertificates): string
    {
        preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $chain, $matches);
        $certificates = $matches[0];
        if ($certificates === []) {
            throw new RuntimeException('The certificate chain is empty or invalid.');
        }

        $key = openssl_pkey_get_private($privateKey);
        $details = $key === false ? false : openssl_pkey_get_details($key);
        if ($details === false || $details['type'] !== OPENSSL_KEYTYPE_RSA) {
            throw new RuntimeException('AbraFlexi requires an RSA private key.');
        }
        if (!openssl_x509_check_private_key($certificates[0], $key)) {
            throw new RuntimeException('The private key does not match the leaf certificate.');
        }

        for ($index = 1; $index < \count($certificates); ++$index) {
            $childInfo = openssl_x509_parse($certificates[$index - 1]);
            $issuerInfo = openssl_x509_parse($certificates[$index]);
            $publicKey = openssl_pkey_get_public($certificates[$index]);
            if ($childInfo === false || $issuerInfo === false || $publicKey === false
                || $childInfo['issuer'] !== $issuerInfo['subject']
                || openssl_x509_verify($certificates[$index - 1], $publicKey) !== 1) {
                throw new RuntimeException('The certificate chain is incomplete or invalid.');
            }
        }

        $last = end($certificates);
        $lastInfo = openssl_x509_parse($last);
        if ($lastInfo === false) {
            throw new RuntimeException('The certificate chain is invalid.');
        }

        preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $trustedCertificates, $roots);
        foreach ($roots[0] as $root) {
            $rootInfo = openssl_x509_parse($root);
            if ($rootInfo === false || $rootInfo['subject'] !== $rootInfo['issuer']
                || $lastInfo['issuer'] !== $rootInfo['subject']) {
                continue;
            }
            $publicKey = openssl_pkey_get_public($root);
            if ($publicKey !== false && openssl_x509_verify($root, $publicKey) === 1
                && openssl_x509_verify($last, $publicKey) === 1) {
                if ($lastInfo['subject'] !== $lastInfo['issuer']) {
                    $chain = rtrim($chain)."\n".$root."\n";
                }

                return rtrim($chain)."\n".trim($privateKey)."\n";
            }
        }

        throw new RuntimeException('No trusted root certificate matches the end of the certificate chain.');
    }
}
