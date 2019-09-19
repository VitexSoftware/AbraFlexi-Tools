#!/usr/bin/php
<?php
$shortopts = "c::"; // Optional value
$longopts  = array(
    "config::",    // Optional value
);
$options = getopt($shortopts, $longopts);

if(empty($options)){
    echo "Create address record in FlexiBee\n\n";
    echo "\nUsage:\n";
    echo "fakeaddress [-c|--config] \n\n";
    echo "default config file is /etc/flexibee/client.json\n";
    exit();
}

if(isset($options['config']) || isset($options['c'])){
    $configFile = isset($options['config']) ? isset($options['config']) : $options['c'];
} else {
    $configFile = '/etc/flexibee/client.json';
}
\Ease\Shared::instanced()->loadConfig($configFile,true);

var_dump($options);
