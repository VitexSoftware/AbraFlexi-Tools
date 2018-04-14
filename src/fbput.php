#!/usr/bin/php
<?php
require_once '../vendor/autoload.php';

$shortopts = "e:i:";
$shortopts .= "c::";
$longopts  = array(
    "evidence:",
    "id:",
    "config::",
);
$options   = getopt($shortopts, $longopts);

if (empty($options)) {
    echo "Update or create an record in FlexiBee\n\n";
    echo "\nUsage:\n";
    echo "fbput -e|--evidence evidence-name -i|--id rowID [-c|--config] [--column-names to put] \n\n";
    echo "example:  \n\n";
    echo "default config file is /etc/flexibee/client.json\n";
    exit();
}

if (isset($options['id']) || isset($options['i'])) {
    $id = isset($options['id']) ? isset($options['id']) : $options['i'];
} else {
    die("row ID is requied\n");
}

if (isset($options['evidence']) || isset($options['e'])) {
    $evidence   = isset($options['evidence']) ? isset($options['evidence']) : $options['e'];
    $infoSource = FlexiPeeHP\FlexiBeeRO::$infoDir.'/Properties.'.$evidence.'.json';
    if (file_exists($infoSource)) {

        $columnsInfo = json_decode(file_get_contents($infoSource), true);
        foreach ($columnsInfo as $columnName => $columnProperties) {
            $columnsAvailble[] = $columnName.'::';
        }

        $columnsToPut = getopt($shortopts, $columnsAvailble);
        unset($columnsToPut['e']);
        unset($columnsToPut['i']);
        unset($columnsToPut['u']);
    }
    $columnsToPut['id'] = $id;
} else {
    die("evidence is requied\n");
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = isset($options['config']) ? isset($options['config']) : $options['c'];
} else {
    $configFile = '/etc/flexibee/client.json';
}
\Ease\Shared::instanced()->loadConfig($configFile);

$grabber = new FlexiPeeHP\FlexiBeeRW(null,
    ['evidence' => $evidence, 'detail' => 'id']);
$grabber->insertToFlexiBee($columnsToPut);

echo $grabber->lastCurlResponse;
if ($grabber->lastResponseCode == 201) {
    exit(0);
} else {
    exit(1);
}
