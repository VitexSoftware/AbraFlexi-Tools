#!/usr/bin/php
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

require_once \dirname(__DIR__).'/vendor/autoload.php';

use AbraFlexi\Banka;
use Ease\Shared;

// Inicializace konfigurace
Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');

$banker = new Banka();

// Najdi všechny bankovní pohyby s nulovou hodnotou
$nulovePolozky = $banker->getColumnsFromAbraFlexi([
    'id', 'sumCelkem', 'kod', 'varSym', 'specSym', 'popis', 'datVyst', 'typPohybuK', 'cisDosle', 'cisObj', 'buc', 'iban', 'mena', 'firma', 'vypisCisDokl',
], [
    'sumCelkem' => 0,
    'limit' => 0,
]);

$count = \count($nulovePolozky);
echo "Nalezeno {$count} bankovních pohybů s nulovou hodnotou:\n";

foreach ($nulovePolozky as $idx => $polozka) {
    $randomAmount = mt_rand(1, 1000); // Náhodná částka 1-1000
    $bankRecord = new Banka(\AbraFlexi\Functions::code($polozka['kod']), ['autoload' => false]);
    $bankRecord->setDataValue('id', \AbraFlexi\Functions::code($polozka['kod']));
    $bankRecord->setDataValue('typDokl', \AbraFlexi\Functions::code('STANDARD'));
    $bankRecord->setDataValue('sumCelkem', $randomAmount);
    $bankRecord->setDataValue('sumOsv', $randomAmount);
    $bankRecord->addArrayToBranch(
        [
            'typPolozkyK' => 'typPolozky.ucetni',
            'doklInt' => \AbraFlexi\Functions::code($polozka['kod']),
            'sumCelkem' => $randomAmount,
        ],
    );

    try {
        $response = $bankRecord->insertToAbraFlexi();
        echo sprintf(
            "[%d] %s | VS: %s | Spec: %s | %s | %s | %s | účet: %s | IBAN: %s | měna: %s | firma: %s | výpis: %s | původní částka: %.2f | nová částka: %.2f\n",
            $idx + 1,
            $polozka['kod'] ?? $polozka['id'],
            $polozka['varSym'] ?? '-',
            $polozka['specSym'] ?? '-',
            $polozka['datVyst'] ?? '-',
            $polozka['typPohybuK'] ?? '-',
            $polozka['popis'] ?? '-',
            $polozka['buc'] ?? '-',
            $polozka['iban'] ?? '-',
            $polozka['mena'] ?? '-',
            $polozka['firma'] ?? '-',
            $polozka['vypisCisDokl'] ?? '-',
            (float) ($polozka['sumCelkem'] ?? 0),
            (float) $randomAmount,
        );
    } catch (\AbraFlexi\Exception $exc) {
        echo $exc->getTraceAsString();
    }
}
