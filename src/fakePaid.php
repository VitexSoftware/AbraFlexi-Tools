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
use AbraFlexi\FakturaVydana;
use Ease\Shared;

// Inicializace konfigurace
Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');

// Načtení všech neuhrazených vydaných faktur
$faktury = new FakturaVydana();

// Získání všech neuhrazených faktur do pole
$fakturyData = $faktury->getColumnsFromAbraFlexi(['id', 'sumCelkem', 'juhSum', 'kod', 'banka', 'varSym', 'specSym', 'datVyst', 'firma'], ['zbyvaUhradit <> 0', 'limit' => 0]);
$total = \count($fakturyData);
$faktury->addStatusMessage("Nalezeno {$total} neuhrazených faktur");

$banker = new Banka();

foreach ($fakturyData as $idx => $faktura) {
    // Zbývá uhradit = sumCelkem - juhSum
    $zbyvaUhradit = (float) ($faktura['sumCelkem'] ?? 0) - (float) ($faktura['juhSum'] ?? 0);
    $progress = $total > 0 ? round((($idx + 1) / $total) * 100, 1) : 100;
    $faktury->addStatusMessage(sprintf("[%d/%d | %5.1f%%] %s: zbývá uhradit %.2f\n", $idx + 1, $total, $progress, $faktura['kod'] ?? $faktura['id'], $zbyvaUhradit));

    if ($zbyvaUhradit > 0) {
        $bankaData = [
            'kod' => 'FP'.$faktura['id'].\Ease\Functions::randomString(),
            'banka' => \AbraFlexi\Functions::code(\Ease\Shared::cfg('ABRAFLEXI_BANK')),
            'typPohybuK' => 'typPohybu.prijem',
            'popis' => 'Automatická úhrada faktury '.($faktura['kod'] ?? ''),
            'varSym' => $faktura['varSym'] ?? $faktura['id'],
            'bezPolozek' => true,
            'datVyst' => date('Y-m-d'),
            'typDokl' => 'code:STANDARD',
            'sumCastka' => $zbyvaUhradit,
            'doklad' => 'fv:'.$faktura['id'],
        ];

        if ($faktura['firma']) {
            $bankaData['firma'] = $faktura['firma'];
        }

        try {
            $pay = $banker->insertToAbraFlexi($bankaData);
            $banker->addStatusMessage("Vytvořena platba pro fakturu {$faktura['kod']} ve výši {$zbyvaUhradit}", 'success');
        } catch (\AbraFlexi\Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }
}
