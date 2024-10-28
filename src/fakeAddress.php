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

\define('EASE_LOGGER', 'syslog|console');

$shortopts = 'c:i:';
$longopts = ['config::', 'iterations::'];
$options = getopt($shortopts, $longopts);

if (empty($options)) {
    echo "Create address record in AbraFlexi\n\n";
    echo "\nUsage:\n";
    echo "fakeaddress [-c|--config path/to.cfg] [-i|--iterations NUM] \n\n";
    echo "default config file is /etc/abraflexi/client.json\n";

    exit;
}

if (isset($options['config']) || isset($options['c'])) {
    $configFile = $options['config'] ?? $options['c'];
} else {
    $configFile = '/etc/abraflexi/client.json';
}

$iterations = (\array_key_exists('iterations', $options) || \array_key_exists('i', $options)) ? (\array_key_exists('iterations', $options) ? (int) ($options['iterations']) : (int) ($options['i'])) : 1;

if (file_exists($configFile)) {
    \Ease\Shared::instanced()->loadConfig($configFile, true);
}

$addresser = new \AbraFlexi\Adresar();
$addresser->logBanner('Fake Address Generator');
$faker = Faker\Factory::create();

for ($index = 0; $index < $iterations; ++$index) {
    $addresser->dataReset();
    $addresser->setData(
        [
            'popis' => $faker->userName,
            'email' => $faker->email,
            'nazev' => $faker->firstName.' '.$faker->lastName,
            'mesto' => $faker->city,
            'ulice' => $faker->streetName,
            'tel' => $faker->phoneNumber,
            'stat' => \AbraFlexi\RO::code($faker->countryCode),
        ],
    );
    $newAddr = $addresser->insertToAbraFlexi();
    $addresser->addStatusMessage('#'.$index.'/'.$iterations.': '.$addresser->getRecordIdent().': '.$addresser->getDataValue('nazev'), ($addresser->lastResponseCode === 201) ? 'success' : 'error');
}
