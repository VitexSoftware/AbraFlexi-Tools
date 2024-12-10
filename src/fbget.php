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

\define('APP_NAME', 'AbraFlexi Get Record');

require '../vendor/autoload.php';

$shortopts = 'e:i:c:vu::';
$options = getopt($shortopts);
$detail = 'id';

if (empty($options)) {
    echo "Obtain a record data from AbraFlexi\n\n";
    echo "\nUsage:\n";
    echo $argv[0]." -e evidence-name -i RowID [-c Path] [-u] [-v] column_name [column_name2 ...] \n\n";
    echo 'example: '.$argv[0]." -e adresar -u -i 333 kod nazev \n\n";
    echo "default config file is /etc/abraflexi/client.json (Override it by -c)\n";

    exit;
}

if (isset($options['id']) || isset($options['i'])) {
    $id = $options['id'] ?? $options['i'];
} else {
    exit("row ID is requied\n");
}

if (isset($options['evidence']) || isset($options['e'])) {
    $evidence = $options['evidence'] ?? $options['e'];

    if (\array_key_exists($evidence, \AbraFlexi\EvidenceList::$evidences)) {
        $columnsToGet = [];
        $columnsInfo = \AbraFlexi\Functions::getOfflineColumnsInfo($evidence);
        unset($argv[0]);

        foreach ($argv as $param) {
            if (($param[0] === '-') && \array_key_exists($param[1], $options)) {
                continue; // Known switch
            }

            if (array_search($param, $options, true)) {
                continue; // Switch's parameter
            }

            if (\array_key_exists($param, $columnsInfo)) {
                $columnsToGet[] = $param;
            } else {
                if (($param !== $evidence) && ($param !== $id) && ($param[0] !== '-')) {
                    exit("column {$param} does not exist in evidence {$evidence} \n");
                }
            }
        }

        if (!empty($columnsToGet)) {
            $detail = 'custom:'.implode(',', $columnsToGet);
        }
    }
    // unknown evidence ?!?
} else {
    exit("evidence is requied\n");
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = isset($options['config']) ?: $options['c'];
} else {
    $configFile = '/etc/abraflexi/client.json';
}

if (file_exists($configFile)) {
    \Ease\Shared::instanced()->loadConfig($configFile, true);
}

$grabber = new AbraFlexi\RO(
    is_numeric($id) ? (int) $id : $id,
    ['evidence' => $evidence, 'detail' => $detail],
);

if (isset($options['v'])) {
    $grabber->logBanner(__FILE__);
}

if (isset($options['show-url']) || isset($options['u'])) {
    echo urldecode($grabber->getApiURL())."\n";
}

if ($grabber->lastResponseCode === 200) {
    echo \json_encode($grabber->getData(), \JSON_PRETTY_PRINT);

    exit(0);
}

echo \json_encode(json_decode($grabber->lastCurlResponse), \JSON_PRETTY_PRINT)."\n";

exit(1);
