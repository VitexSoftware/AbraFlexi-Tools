#!/usr/bin/php
<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

define('EASE_LOGGER', 'syslog|console');

$shortopts = "c:i:";
$longopts = array("config::", "iterations::");
$options = getopt($shortopts, $longopts);

if (empty($options)) {
    echo "Create address record in FlexiBee\n\n";
    echo "\nUsage:\n";
    echo "fakeaddress [-c|--config path/to.cfg] [-i|--iterations NUM] \n\n";
    echo "default config file is /etc/flexibee/client.json\n";
    exit();
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = isset($options['config']) ? $options['config'] : $options['c'];
} else {
    $configFile = '/etc/flexibee/client.json';
}

$iterations = (array_key_exists('iterations', $options) || array_key_exists('i', $options)) ? (array_key_exists('iterations', $options) ? intval($options['iterations']) : intval($options['i'])) : 1;

if (file_exists($configFile)) {
    \Ease\Shared::instanced()->loadConfig($configFile, true);
}

$addresser = new \FlexiPeeHP\Adresar();
$addresser->logBanner('Fake Address Generator');
$faker = Faker\Factory::create();
for ($index = 0; $index < $iterations; $index++) {
    $addresser->dataReset();
    $addresser->setData(
            [
                'popis' => $faker->userName,
                'email' => $faker->email,
                'nazev' => $faker->firstName . ' ' . $faker->lastName,
                'mesto' => $faker->city,
                'ulice' => $faker->streetName,
                'tel' => $faker->phoneNumber,
                'stat' => \FlexiPeeHP\FlexiBeeRO::code($faker->countryCode),
            ]
    );
    $newAddr = $addresser->insertToFlexiBee();
    $addresser->addStatusMessage('#' . $index . '/' . $iterations . ': ' . $addresser->getRecordIdent() . ': ' . $addresser->getDataValue('nazev'), ($addresser->lastResponseCode == 201) ? 'success' : 'error');
}
