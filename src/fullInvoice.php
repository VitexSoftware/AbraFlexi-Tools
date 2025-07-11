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

use AbraFlexi\FakturaVydana;
use Ease\Shared;

// Inicializace konfigurace
Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');

$invoicer = new FakturaVydana();

// Najdi všechny bankovní pohyby s nulovou hodnotou
$nulovePolozky = $invoicer->getColumnsFromAbraFlexi(['id', 'sumCelkem', 'kod'], [
    'sumCelkem' => 0,
    'limit' => 0,
]);

$count = count($nulovePolozky);
echo "Nalezeno {$count} faktur s nulovou částkou.\n";

$goodsNames = [
    'Notebook', 'Monitor', 'Klávesnice', 'Myš', 'Tiskárna', 'Router', 'Telefon', 'Tablet', 'USB disk', 'Sluchátka',
    'Webkamera', 'Reproduktor', 'SSD disk', 'Grafická karta', 'Procesor', 'Paměť RAM', 'Záložní zdroj', 'Switch', 'Projektor', 'Mikrofon'
];

foreach ($nulovePolozky as $idx => $faktura) {
    $randomAmount = mt_rand(1, 10000); // Náhodná částka 1-10000
    $randomPopis = $goodsNames[array_rand($goodsNames)];
    $invoicer->dataReset();
    try {
    $result = $invoicer->insertToAbraFlexi([
        'id' => $faktura['id'],
        'bezPolozek' => true,
        'bankovniUcet' => 'code:BENCHMARK',
        'popis' => $randomPopis,
        'sumZklZakl' => $randomAmount,
    ]);
    } catch (\Exception $ex) {
        
    }
    echo sprintf(
        "[%d/%d] Faktura %s (ID: %s) nastavena na částku: %d Kč, popis: %s\n",
        $idx + 1,
        $count,
        $faktura['kod'] ?? $faktura['id'],
        $faktura['id'],
        $randomAmount,
        $randomPopis
    );
}

