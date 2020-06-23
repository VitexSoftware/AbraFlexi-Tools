<?php

$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

define('EASE_APPNAME', 'ReportUploader');
define('EASE_LOGGER', 'syslog|console');

if (empty(getenv('FLEXIBEE_URL'))) {
    echo "Please set up FlexiBee client configuration environment: \n\n";
    echo "FLEXIBEE_URL=https://demo.flexibee.eu:5434\n";
    echo "FLEXIBEE_PASSWORD=winstrom\n";
    echo "FLEXIBEE_LOGIN=winstrom\n";
    echo "FLEXIBEE_COMPANY=demo_de\n";
}

if ($argc < 3) {
    echo "usage: " . $argv[0] . " <recordIdent> <formInfoCode> <reportfile> \n";
    echo "example: " . $argv[0] . "  code:PokladDen pokDenik WinstromReports/vykazAnalyzaZakazky/analyzaZakazky.jrxml \n";
} else {
    $reportID = $argv[1];

    if ($argc == 3) {
        if (is_file($argv[2])) {
            $reportFile = $argv[2];
        } else {
            $formCode = $argv[2];
            $reportFile = $argv[3];
        }
    }

    if (strstr($reportFile, '.jrxml')) {
        system('jaspercompiler ' . $reportFile);
        $reportFile = str_replace('.jrxml', '.jasper', $reportFile);
    }


    if (file_exists($reportFile)) {

        $reporter = new FlexiPeeHP\Report($reportID);
        $oldReportId = intval($reporter->getDataValue('hlavniReport'));
        $attachment = \FlexiPeeHP\Priloha::addAttachmentFromFile($reporter, $reportFile);
        if ($reporter->sync(['hlavniReport' => $attachment->getRecordID(), 'id' => $reporter->getRecordID()])) {
            if ($oldReportId) {
                $attachment->deleteFromFlexiBee($oldReportId);
            }
            $reporter->addStatusMessage(_('Report updated'), 'success');
        }
    }
}


