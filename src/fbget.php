<?php
/**
 * FlexiBee Tools  - Get
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2020 Vitex Software
 */


$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

$shortopts = "e:i:c:vu::";
$options = getopt($shortopts);

if (empty($options)) {
    echo "Obtain a record data from FlexiBee\n\n";
    echo "\nUsage:\n";
    echo $argv[0] . " -e evidence-name -i RowID [-c Path] [-u] [-v] column_name [column_name2 ...] \n\n";
    echo "example: " . $argv[0] . " -e adresar -u -i 333 kod nazev \n\n";
    echo "default config file is /etc/flexibee/client.json (Override it by -c)\n";
    exit();
}

if (isset($options['id']) || isset($options['i'])) {
    $id = isset($options['id']) ? $options['id'] : $options['i'];
} else {
    die("row ID is requied\n");
}

if (isset($options['evidence']) || isset($options['e'])) {
    $evidence = isset($options['evidence']) ? $options['evidence'] : $options['e'];
    if (array_key_exists($evidence, \FlexiPeeHP\Structure::$evidence)) {
        $columnsToGet = [];
        $columnsInfo = \FlexiPeeHP\Structure::$evidence[$evidence];
        unset($argv[0]);
        foreach ($argv as $param) {
            if (($param[0] == '-') && array_key_exists($param[1], $options)) {
                continue; //Known switch
            }

            if (array_search($param, $options)) {
                continue; //Switch's parameter
            }

            if (array_key_exists($param, $columnsInfo)) {
                $columnsToGet[] = $param;
            } else {
                if (($param != $evidence) && ($param != $id) && ($param[0] != '-')) {
                    die("column $param does not exist in evidence $evidence \n");
                }
            }
        }
        if (empty($columnsToGet)) {
            $detail = 'id';
        } else {
            $detail = 'custom:' . implode(',', $columnsToGet);
        }
    } else {
        //unknown evidence ?!?
    }
} else {
    die("evidence is requied\n");
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = isset($options['config']) ? isset($options['config']) : $options['c'];
} else {
    $configFile = '/etc/flexibee/client.json';
}
if (file_exists($configFile)) {
    \Ease\Shared::instanced()->loadConfig($configFile, true);
}

$grabber = new FlexiPeeHP\FlexiBeeRO(is_numeric($id) ? intval($id) : $id,
        ['evidence' => $evidence, 'detail' => $detail]);

if (isset($options['v'])) {
    $grabber->logBanner(__FILE__);
}

if (isset($options['show-url']) || isset($options['u'])) {
    echo urldecode($grabber->getApiURL()) . "\n";
}

if ($grabber->lastResponseCode == 200) {
    echo \json_encode($grabber->getData(), JSON_PRETTY_PRINT);
    exit(0);
} else {
    echo \json_encode(json_decode($grabber->lastCurlResponse), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}
