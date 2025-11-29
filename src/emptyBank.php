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

// Získání všech položek v evidenci banka
$polozky = $banker->getColumnsFromAbraFlexi(['id', 'kod'], ['limit' => 0]);
$count = \count($polozky);
echo "Nalezeno {$count} bankovních položek ke smazání.\n";

foreach ($polozky as $idx => $polozka) {
    try {
        $banker->deleteFromAbraFlexi($polozka['id']);
        echo sprintf("[%d/%d] Smazána položka ID: %s, kód: %s\n", $idx + 1, $count, $polozka['id'], $polozka['kod'] ?? '-');
    } catch (\Exception $ex) {
        echo sprintf("[%d/%d] Chyba při mazání ID: %s, kód: %s - %s\n", $idx + 1, $count, $polozka['id'], $polozka['kod'] ?? '-', $ex->getMessage());
    }
}
