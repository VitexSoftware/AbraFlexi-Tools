<?php

declare(strict_types=1);

namespace Tests;

use AbraFlexi\Tools\CertificateBundle;
use PHPUnit\Framework\TestCase;

final class CertificateBundleTest extends TestCase
{
    private function certificate(string $name, mixed $signer, mixed $signingKey): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);
        $csr = openssl_csr_new(['commonName' => $name], $key, ['digest_alg' => 'sha256']);
        self::assertNotFalse($csr);
        $certificate = openssl_csr_sign($csr, $signer, $signingKey ?? $key, 1, ['digest_alg' => 'sha256']);
        self::assertNotFalse($certificate);
        self::assertTrue(openssl_x509_export($certificate, $pem));
        self::assertTrue(openssl_pkey_export($key, $privateKey));

        return [$certificate, $key, $pem, $privateKey];
    }

    public function testAppendsTrustedRootAndAvoidsDuplicatingIt(): void
    {
        [$root, $rootKey, $rootPem] = $this->certificate('Test Root', null, null);
        [$intermediate, $intermediateKey, $intermediatePem] = $this->certificate('Intermediate', $root, $rootKey);
        [, , $leafPem, $leafKey] = $this->certificate('Leaf', $intermediate, $intermediateKey);
        $chain = $leafPem.$intermediatePem;

        self::assertSame($chain.$rootPem.$leafKey, CertificateBundle::create($chain, $leafKey, $rootPem));
        self::assertSame($chain.$rootPem.$leafKey, CertificateBundle::create($chain.$rootPem, $leafKey, $rootPem));
    }

    public function testRejectsUntrustedRoot(): void
    {
        [$root, $rootKey] = $this->certificate('Test Root', null, null);
        [, , $leafPem, $leafKey] = $this->certificate('Leaf', $root, $rootKey);
        [, , $otherRoot] = $this->certificate('Other Root', null, null);

        $this->expectExceptionMessage('No trusted root certificate');
        CertificateBundle::create($leafPem, $leafKey, $otherRoot);
    }

    public function testRejectsMismatchedPrivateKey(): void
    {
        [$root, $rootKey, $rootPem] = $this->certificate('Test Root', null, null);
        [, , $leafPem] = $this->certificate('Leaf', $root, $rootKey);
        [, , , $otherKey] = $this->certificate('Other', null, null);

        $this->expectExceptionMessage('private key does not match');
        CertificateBundle::create($leafPem, $otherKey, $rootPem);
    }

    public function testRejectsInvalidIntermediate(): void
    {
        [$root, $rootKey, $rootPem] = $this->certificate('Test Root', null, null);
        [, , $leafPem, $leafKey] = $this->certificate('Leaf', $root, $rootKey);
        [, , $unrelatedPem] = $this->certificate('Unrelated', null, null);

        $this->expectExceptionMessage('certificate chain is incomplete or invalid');
        CertificateBundle::create($leafPem.$unrelatedPem, $leafKey, $rootPem);
    }
}
