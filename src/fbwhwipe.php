<?php
/**
 * FlexiBee WebHooks Wipe
 */
$loaderPath = realpath(__DIR__."/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__.'/../vendor/autoload.php';
}

define('EASE_APPNAME', 'Wipe FlexiBee WebHooks');
define('EASE_LOGGER', 'syslog|console');

$config_file = $argc > 1 ? $argv[1] : '/etc/flexibee/client.json';

if (file_exists($config_file)) {
    \Ease\Shared::instanced()->loadConfig($config_file,true);

    $hooker = new \FlexiPeeHP\Hooks();
    $hooks  = $hooker->getFlexiData();
    if (!isset($hooks['message']) && is_array($hooks) && !empty(current($hooks)) && count(current($hooks))) {
        foreach ($hooks as $hookinfo) {
            if ($hooker->unregister($hookinfo['id'])) {
                $hooker->addStatusMessage(sprintf(_('Hook %s was unregistered'),
                        $hookinfo['url']), 'success');
            } else {
                $hooker->addStatusMessage(sprintf(_('Hook %s was not unregistered'),
                        $hookinfo['url']), 'warning');
            }
        }
    }
} else {
    \Ease\Shared::instanced()->addStatusMessage(_('Cannot read %s'),
        $config_file, 'error');
}

