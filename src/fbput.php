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

\define('APP_NAME', 'AbraFlexi Put Record');

require \dirname(__DIR__).'/vendor/autoload.php';

$columnsToPut = [];
$shortopts = 'c:e:i:v:u::';
$options = getopt($shortopts);

if (empty($options)) {
    echo "Update or create an record in AbraFlexi\n\n";
    echo "\nUsage:\n";
    echo $argv[0]." -e evidence-name [-iRowID] [-c path] [-u] [-v] [--colum-name=value] [--colum-name2=value2] ... \n\n";
    echo 'example:  '.$argv[0]." -e adresar -u -i333 --nazev=zmeneno \n\n";
    echo "default config file is /etc/abraflexi/client.json (Override it by -c)\n";

    exit;
}

if (isset($options['evidence']) || isset($options['e'])) {
    $evidence = $options['evidence'] ?? $options['e'];
    $infoSource = AbraFlexi\Functions::$infoDir.'/Properties.'.$evidence.'.json';

    if (file_exists($infoSource)) {
        $columnsAvailble = [];
        $columnsInfo = json_decode(file_get_contents($infoSource), true);

        foreach ($columnsInfo as $columnName => $columnProperties) {
            $columnsAvailble[] = $columnName.'::';
        }

        $columnsToPut = getopt($shortopts, $columnsAvailble);
        unset($columnsToPut['v'], $columnsToPut['e'], $columnsToPut['i'], $columnsToPut['u'],$columnsToPut['c']);
    }
} else {
    exit("evidence is requied\n");
}

if (isset($options['id']) || isset($options['i'])) {
    $id = $options['id'] ?? $options['i'];
    $columnsToPut['id'] = $id;
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = $options['config'] ?? $options['c'];
} else {
    $configFile = '.env';
}

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], $configFile);

$grabber = new AbraFlexi\RW(
    null,
    ['evidence' => $evidence, 'detail' => 'id'],
);

if (isset($options['v'])) {
    $grabber->logBanner();
}

$grabber->insertToAbraFlexi($columnsToPut);

if (isset($options['show-url']) || isset($options['u'])) {
    echo urldecode($grabber->getApiURL())."\n";
}

echo \json_encode(\json_decode($grabber->lastCurlResponse), \JSON_PRETTY_PRINT)."\n";

if ($grabber->lastResponseCode === 201) {
    exit(0);
}

exit(1);
