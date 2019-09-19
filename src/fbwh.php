<?php
$loaderPath = realpath(__DIR__."/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__.'/../vendor/autoload.php';
}

define('EASE_APPNAME', 'FlexiBee WebHook Establisher');
define('EASE_LOGGER', 'syslog|console');

if ($argc < 1) {
    echo "usage: ".$argv[0]." http://webhook.processor/url [xml|json] [custom/config.json] \n";
} else {
    $hookurl = $argv[1];
    $format  = array_key_exists(2, $argv) ? $argv[2] : 'json';
    $config  = array_key_exists(3, $argv) ? $argv[3] : '/etc/flexibee/client.json';

    if (file_exists($config)) {
        \Ease\Shared::instanced()->loadConfig($config, true);
    } else {
        \Ease\Shared::instanced()->addStatusMessage(_('Cannot read %s'),
            $config_file, 'error');
        die('unconfigured');
    }

    $changer = new \FlexiPeeHP\Changes();
    if (!$changer->getStatus()) {
        $changer->enable();
    }

    if (strlen($hookurl)) {
        $hooker = new \FlexiPeeHP\Hooks();
        $hooker->setDataValue('skipUrlTest', 'true');
//        $hooker->setDataValue('skipUrlTest', 'false');
//        $hooker->setDataValue('lastVersion', $lastversion);
//        $hooker->setDataValue('secKey', $secKey);

        $hookResult = $hooker->register($hookurl, $format);
        if ($hookResult) {
            $hooker->addStatusMessage(sprintf(_('Hook %s was registered'),
                    $hookurl), 'success');
            $hookurl = '';
        } else {
            $hooker->addStatusMessage(sprintf(_('Hook %s not registered'),
                    $hookurl), 'warning');
        }
    }
}


