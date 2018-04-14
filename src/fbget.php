<?php
$loaderPath = __DIR__."/../../../autoload.php";
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$shortopts = "e:i:";
$shortopts .= "u::";
$options   = getopt($shortopts);

if (empty($options)) {
    echo "Obtain a record data from FlexiBee\n\n";
    echo "\nUsage:\n";
    echo "fbput -e evidence-name -iRowID [-cPath] [-u] [--colum-name=value] [--colum-name2=value2] \n\n";
    echo "example: flexibeeget -e adresar -u -i 333 kod nazev \n\n";
    echo "default config file is /etc/flexibee/client.json\n";
    exit();
}

if (isset($options['id']) || isset($options['i'])) {
    $id = isset($options['id']) ? $options['id'] : $options['i'];
} else {
    die("row ID is requied\n");
}

if (isset($options['evidence']) || isset($options['e'])) {
    $evidence   = isset($options['evidence']) ? $options['evidence'] : $options['e'];
    $infoSource = FlexiPeeHP\FlexiBeeRO::$infoDir.'/Properties.'.$evidence.'.json';
    if (file_exists($infoSource)) {
        $columnsToGet = [];
        $columnsInfo  = json_decode(file_get_contents($infoSource), true);
        unset($argv[0]);
        foreach ($argv as $param) {
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
            $detail = 'custom:'.implode(',', $columnsToGet);
        }
    }
} else {
    die("evidence is requied\n");
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = isset($options['config']) ? isset($options['config']) : $options['c'];
} else {
    $configFile = '/etc/flexibee/client.json';
}
\Ease\Shared::instanced()->loadConfig($configFile);

$grabber = new FlexiPeeHP\FlexiBeeRO(is_numeric($id) ? intval($id) : $id,
    ['evidence' => $evidence, 'detail' => $detail]);

if (isset($options['show-url']) || isset($options['u'])) {
    echo $grabber->getApiURL()."\n";
}

if ($grabber->lastResponseCode == 200) {
    echo \json_encode($grabber->getData(), JSON_PRETTY_PRINT);
    exit(0);
} else {
    echo $grabber->lastCurlResponse;
    exit(1);
}
