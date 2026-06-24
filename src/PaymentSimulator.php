#!/usr/bin/php
<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexi Tools package
 *
 * https://github.com/VitexSoftware/AbraFlexi-Tools
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once \dirname(__DIR__).'/vendor/autoload.php';

use AbraFlexi\Banka;
use AbraFlexi\FakturaVydana;
use Ease\Shared;

/**
 * Payment behaviour profiles.
 *
 * Assigned via AbraFlexi customer labels with prefix SIM_:
 *   SIM_REGULAR     – pays exact amount every run
 *   SIM_LATE_DOUBLE – skips one cycle; when ≥2 invoices unpaid pays all at once
 *   SIM_OVER_PAYER  – pays 1–50 CZK more than owed
 *   SIM_UNDER_PAYER – pays 1–50 CZK less than owed (creates remainder)
 *   SIM_TYPO_VARSYM – correct amount but mangled variable symbol
 *   SIM_NON_PAYER   – never pays (triggers reminder escalation)
 */

Shared::init(
    ['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'ABRAFLEXI_BANK'],
    \dirname(__DIR__).'/.env',
);

$report = [
    'exitcode' => 0,
    'status' => 'success',
    'timestamp' => date(\DateTimeInterface::ATOM),
    'message' => '',
    'metrics' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
    'payments' => [],
];

// ------------------------------------------------------------------
// 1. Load all unpaid invoices grouped by customer
// ------------------------------------------------------------------
$invoicer = new FakturaVydana();
$rawInvoices = $invoicer->getColumnsFromAbraFlexi(
    ['id', 'kod', 'varSym', 'firma', 'sumCelkem', 'zbyvaUhradit', 'datVyst', 'datSplat'],
    ['zbyvaUhradit > 0', 'storno eq false', 'limit' => 0],
);

if (empty($rawInvoices)) {
    $report['message'] = 'No unpaid invoices found.';
    echo json_encode($report, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
    exit(0);
}

// Group invoices by customer code
$byCustomer = [];

foreach ($rawInvoices as $inv) {
    $firma = $inv['firma'] ?? 'UNKNOWN';
    $byCustomer[$firma][] = $inv;
}

// ------------------------------------------------------------------
// 2. Load customer labels to determine simulation profiles
// ------------------------------------------------------------------
$addressBook = new \AbraFlexi\Adresar();
$allCustomers = $addressBook->getColumnsFromAbraFlexi(
    ['kod', 'stitky'],
    ['limit' => 0],
);
$customerLabels = [];

foreach ($allCustomers as $c) {
    $labels = \AbraFlexi\Stitek::listToArray((string) ($c['stitky'] ?? ''));
    $customerLabels[(string) $c['kod']] = $labels;
}

// ------------------------------------------------------------------
// 3. Determine profile from labels
// ------------------------------------------------------------------
$profileOrder = ['SIM_NON_PAYER', 'SIM_LATE_DOUBLE', 'SIM_TYPO_VARSYM', 'SIM_UNDER_PAYER', 'SIM_OVER_PAYER', 'SIM_REGULAR'];

function getProfile(string $customerCode, array $customerLabels): string
{
    global $profileOrder;
    $labels = $customerLabels[$customerCode] ?? [];

    foreach ($profileOrder as $profileLabel) {
        if (\in_array($profileLabel, $labels, true)) {
            return str_replace('SIM_', '', $profileLabel);
        }
    }

    return 'REGULAR';
}

// ------------------------------------------------------------------
// 4. Mangle a variable symbol (one digit missing or one extra)
// ------------------------------------------------------------------
function mutateVarSym(string $varSym): string
{
    if (strlen($varSym) < 2) {
        return $varSym.(string) random_int(0, 9);
    }

    // 50 % chance: remove one digit; 50 %: insert extra digit
    if (random_int(0, 1) === 0) {
        $pos = random_int(0, strlen($varSym) - 1);

        return substr($varSym, 0, $pos).substr($varSym, $pos + 1);
    }

    $pos = random_int(0, strlen($varSym));
    $digit = (string) random_int(0, 9);

    return substr($varSym, 0, $pos).$digit.substr($varSym, $pos);
}

// ------------------------------------------------------------------
// 5. Create bank records
// ------------------------------------------------------------------
$banker = new Banka();
$bankCode = \AbraFlexi\Functions::code(Shared::cfg('ABRAFLEXI_BANK'));
$bankDocType = Shared::cfg('ABRAFLEXI_BANK_DOCTYPE', 'STANDARD');

foreach ($byCustomer as $firma => $invoices) {
    $profile = getProfile($firma, $customerLabels);

    if ($profile === 'NON_PAYER') {
        foreach ($invoices as $inv) {
            $report['metrics']['skipped']++;
            $report['payments'][] = ['invoice' => $inv['kod'], 'profile' => $profile, 'action' => 'skipped'];
        }

        continue;
    }

    // LATE_DOUBLE: only pay when ≥2 invoices are outstanding (simulates catching up)
    if ($profile === 'LATE_DOUBLE' && \count($invoices) < 2) {
        foreach ($invoices as $inv) {
            $report['metrics']['skipped']++;
            $report['payments'][] = ['invoice' => $inv['kod'], 'profile' => $profile, 'action' => 'waiting_for_second_invoice'];
        }

        continue;
    }

    foreach ($invoices as $inv) {
        $remaining = (float) $inv['zbyvaUhradit'];

        if ($remaining <= 0) {
            continue;
        }

        $varSym = (string) ($inv['varSym'] ?? $inv['id']);
        $amount = $remaining;

        switch ($profile) {
            case 'OVER_PAYER':
                $amount = $remaining + random_int(1, 50);

                break;
            case 'UNDER_PAYER':
                $amount = max(1, $remaining - random_int(1, 50));

                break;
            case 'TYPO_VARSYM':
                $varSym = mutateVarSym($varSym);

                break;
            case 'LATE_DOUBLE':
            case 'REGULAR':
            default:
                // amount and varSym unchanged
                break;
        }

        $bankaData = [
            'kod' => 'SIM'.$inv['id'].\Ease\Functions::randomString(4),
            'banka' => $bankCode,
            'typPohybuK' => 'typPohybu.prijem',
            'popis' => "[SIM:{$profile}] Simulovaná platba faktury ".($inv['kod'] ?? ''),
            'varSym' => $varSym,
            'bezPolozek' => true,
            'datVyst' => date('Y-m-d'),
            'typDokl' => 'code:'.$bankDocType,
            'sumCastka' => $amount,
        ];

        // Only link via doklad when varsym is correct (TYPO_VARSYM must rely on matcher)
        if ($profile !== 'TYPO_VARSYM') {
            $bankaData['doklad'] = 'fv:'.$inv['id'];
        }

        if (!empty($inv['firma'])) {
            $bankaData['firma'] = $inv['firma'];
        }

        try {
            $banker->insertToAbraFlexi($bankaData);
            $report['metrics']['created']++;
            $report['payments'][] = [
                'invoice' => $inv['kod'],
                'profile' => $profile,
                'amount' => $amount,
                'varSym' => $varSym,
                'action' => 'payment_created',
            ];
        } catch (\AbraFlexi\Exception $exc) {
            $report['exitcode'] = 1;
            $report['metrics']['errors']++;
            $report['payments'][] = [
                'invoice' => $inv['kod'],
                'profile' => $profile,
                'action' => 'error',
                'error' => $exc->getMessage(),
            ];
        }
    }
}

// ------------------------------------------------------------------
// 6. Report
// ------------------------------------------------------------------
$c = $report['metrics']['created'];
$s = $report['metrics']['skipped'];
$e = $report['metrics']['errors'];
$report['message'] = "Created: {$c} payments, Skipped: {$s} (NON_PAYER/LATE), Errors: {$e}";

if ($report['exitcode'] !== 0) {
    $report['status'] = 'warning';
}

$resultFile = Shared::cfg('RESULT_FILE', 'php://stdout');
file_put_contents($resultFile, json_encode($report, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

exit($report['exitcode']);
