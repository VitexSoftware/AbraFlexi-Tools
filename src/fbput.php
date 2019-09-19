<?php
$loaderPath = realpath(__DIR__."/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$shortopts = "c:e:i:v:u::";
$options   = getopt($shortopts);

if (empty($options)) {
    echo "Update or create an record in FlexiBee\n\n";
    echo "\nUsage:\n";
    echo $argv[0]." -eevidence-name [-iRowID] [-c path] [-u] [-v] [--colum-name=value] [--colum-name2=value2] ... \n\n";
    echo "example:  ".$argv[0]." -e adresar -u -i333 --nazev=zmeneno \n\n";
    echo "default config file is /etc/flexibee/client.json (Override it by -c)\n";
    exit();
}

if (isset($options['evidence']) || isset($options['e'])) {
    $evidence   = isset($options['evidence']) ? $options['evidence'] : $options['e'];
    $infoSource = FlexiPeeHP\FlexiBeeRO::$infoDir.'/Properties.'.$evidence.'.json';
    if (file_exists($infoSource)) {

        $columnsInfo = json_decode(file_get_contents($infoSource), true);
        foreach ($columnsInfo as $columnName => $columnProperties) {
            $columnsAvailble[] = $columnName.'::';
        }

        $columnsToPut = getopt($shortopts, $columnsAvailble);
        unset($columnsToPut['v']);
        unset($columnsToPut['e']);
        unset($columnsToPut['i']);
        unset($columnsToPut['u']);
    }
} else {
    die("evidence is requied\n");
}

if (isset($options['id']) || isset($options['i'])) {
    $id                 = isset($options['id']) ? $options['id'] : $options['i'];
    $columnsToPut['id'] = $id;
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = isset($options['config']) ? $options['config'] : $options['c'];
} else {
    $configFile = '/etc/flexibee/client.json';
}
\Ease\Shared::instanced()->loadConfig($configFile);

$grabber = new FlexiPeeHP\FlexiBeeRW(null,
    ['evidence' => $evidence, 'detail' => 'id']);

if (isset($options['v'])) {
    $grabber->logBanner(__FILE__);
}

$grabber->insertToFlexiBee($columnsToPut);

if (isset($options['show-url']) || isset($options['u'])) {
    echo urldecode($grabber->getApiURL())."\n";
}

echo \json_encode(\json_decode($grabber->lastCurlResponse), JSON_PRETTY_PRINT)."\n";
if ($grabber->lastResponseCode == 201) {
    exit(0);
} else {
    exit(1);
}
