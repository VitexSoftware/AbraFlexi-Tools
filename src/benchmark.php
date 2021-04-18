<?php

/**
 * AbraFlexi Tools  - Benchmark
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2020-2021 Vitex Software
 */
$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

define('EASE_APPNAME', 'AbraFlexi Benchmark');
define('EASE_LOGGER', 'syslog|console');

if (empty(('ABRAFLEXI_URL'))) {
    echo "Please set up AbraFlexi client configuration environment: \n\n";
    echo "ABRAFLEXI_URL=https://demo.abraflexi.eu:5434\n";
    echo "ABRAFLEXI_PASSWORD=winstrom\n";
    echo "ABRAFLEXI_LOGIN=winstrom\n";
    echo "ABRAFLEXI_COMPANY=demo_de\n";
    exit(1);
}

class Prober extends \AbraFlexi\RW {

    /**
     *
     * @var array 
     */
    public $benchmark = [];

    /**
     *  
     * @var RW
     */
    private $banka = null;
    private $cashType;
    private $pricelist;

    /**
     * Perform this cycles count
     * @var int
     */
    public $cycles = 1;

    /**
     * Seconds to wait before each test
     * @var int
     */
    public $delay = 0;

    /**
     * Benchmark version
     * @var string
     */
    private $version = '1.1';

    public function __construct($init = null, $options = array()) {
        parent::__construct($init, $options);
        $this->logBanner('AbraFlexi Prober v' . $this->version);
    }

    /**
     * 
     * @param string $timerName
     * @param boolean $writing Is this inset type opration ?          
     */
    function timerStart($timerName, $writing = null) {
        if (is_null($writing)) {
            $this->benchmark[$this->cycles][$timerName] = ['start' => microtime()];
        } else {
            $this->benchmark[$this->cycles][$timerName][$writing ? 'write' : 'read'] = ['start' => microtime()];
        }
    }

    /**
     * Cout the time pass
     * 
     * @param string $timerName
     * @param boolean $writing           
     */
    function timerStop($timerName, $writing = null) {
        if (is_null($writing)) {
            $this->benchmark[$this->cycles][$timerName]['end'] = microtime();
        } else {
            $this->benchmark[$this->cycles][$timerName][$writing ? 'write' : 'read']['end'] = microtime();
        }
        sleep($this->delay);
    }

    /**
     * 
     * @param array $startEnd
     * 
     * @return string
     */
    function timerValue($startEnd) {
        $time_start = explode(' ', $startEnd['start']);
        $time_end = explode(' ', $startEnd['end']);
        return number_format(($time_end[1] + $time_end[0] - ($time_start[1] + $time_start[0])), 3);
    }

    /**
     * Testing address record
     * 
     * @return \AbraFlexi\Adresar
     */
    function createAddress() {
        $faker = Faker\Factory::create();
        $checker = new AbraFlexi\Adresar();
        $checker->setData(
                [
                    'popis' => $faker->userName,
                    'email' => $faker->email,
                    'nazev' => $faker->firstName . ' ' . $faker->lastName,
                    'mesto' => $faker->city,
                    'ulice' => $faker->streetName,
                    'tel' => $faker->phoneNumber,
                    'stat' => \AbraFlexi\RO::code($faker->countryCode),
                ]
        );

        $this->timerStart('Address', 'write');
        $checker->insertToAbraFlexi();
        $this->timerStop('Address', 'write');
        $checker->addStatusMessage($checker->getRecordIdent() . ': ' . $checker->getDataValue('nazev'), ($checker->lastResponseCode == 201) ? 'success' : 'error');
        return $checker;
    }

    /**
     * Testing bank move
     * 
     * @return \AbraFlexi\Banka
     */
    function createBankMove() {
        $yesterday = new \DateTime();
        $yesterday->modify('-1 day');

        $bdata = [
            'kod' => 'Benchmark:' . time(),
            'banka' => $this->banka,
            'typPohybuK' => 'typPohybu.prijem',
            'popis' => 'AbraFlexi Benchmark record',
            'varSym' => \Ease\Functions::randomNumber(1111, 9999),
            'specSym' => \Ease\Functions::randomNumber(111, 999),
            'bezPolozek' => true,
            'datVyst' => \AbraFlexi\RO::dateToFlexiDate($yesterday),
            'typDokl' => \AbraFlexi\RO::code('STANDARD')
        ];

        $checker = new AbraFlexi\Banka($bdata);
        $this->timerStart('Bank Move', true);
        $checker->insertToAbraFlexi();
        $this->timerStop('Bank Move', true);
        return $checker;
    }

    /**
     * 
     * @return \AbraFlexi\PokladniPohyb
     */
    function createCashMove() {
        $checker = new AbraFlexi\PokladniPohyb();
        $this->timerStart('Cash Move', true);
        $cashMove = [
            'cisDosle' => time(),
            'kod' => \AbraFlexi\RO::code('CASH_' . time()),
            'typDokl' => $this->cashType,
            'pokladna' => $this->cash,
            'polozkyDokladu' => [
                'Nazev' => 'Test',
                'typPolozkyK' => 'typPolozky.obecny',
                'mnozMj' => 1,
                'cenaMj' => \Ease\Functions::randomNumber(1111, 9999),
            ],
            'popis' => 'benchmark',
            'typPohybuK' => 'typPohybu.prijem',
            'datVyst' => \AbraFlexi\RO::dateToFlexiDate(new DateTime()),
        ];
        $checker->insertToAbraFlexi($cashMove);
        $this->timerStop('Cash Move', true);
        return $checker;
    }

    /**
     * 
     * @return \AbraFlexi\Cenik
     */
    function createPricelistItem() {
        $checker = new AbraFlexi\Cenik();
        $this->timerStart('Pricelist Item', true);
        $pricelistItem = [
            'kod' => \AbraFlexi\RO::code('PRICELIST_' . time()),
            'nazev' => strval(time()),
        ];
        $checker->insertToAbraFlexi($pricelistItem);
        $this->timerStop('Pricelist Item', true);
        return $checker;
    }

    /**
     * 
     * @return \AbraFlexi\FakturaVydana
     */
    function createInvoice() {

        $yesterday = new \DateTime();
        $yesterday->modify('-1 day');
        $testCode = 'TEST_' . time();

        $idata = [
            'kod' => $testCode,
            'varSym' => \Ease\Functions::randomNumber(1111, 9999),
            'specSym' => \Ease\Functions::randomNumber(111, 999),
            'bezPolozek' => true,
            'popis' => 'AbraFlexi Test invoice',
            'datVyst' => \AbraFlexi\RO::dateToFlexiDate($yesterday),
            'typDokl' => \AbraFlexi\RO::code('FAKTURA')
        ];

        $checker = new AbraFlexi\FakturaVydana($idata);
        $this->timerStart('Invoice', true);
        $checker->insertToAbraFlexi();
        $this->timerStop('Invoice', true);
        return $checker;
    }

    /**
     * 
     * @param \AbraFlexi\Adresar $identifier
     * 
     * @return \AbraFlexi\Adresar
     */
    function readAddress($identifier) {
        $checker = new AbraFlexi\Adresar();
        $this->timerStart('Address', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Address', false);
        return $checker;
    }

    /**
     * 
     * @param \AbraFlexi\Banka $identifier
     * 
     * @return \AbraFlexi\Banka
     */
    function readBankMove($identifier) {
        $checker = new AbraFlexi\Banka();
        $this->timerStart('Bank Move', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Bank Move', false);
        return $checker;
    }

    /**
     * 
     * @param \AbraFlexi\PokladniPohyb $identifier
     * 
     * @return \AbraFlexi\PokladniPohyb
     */
    function readCashMove($identifier) {
        $checker = new AbraFlexi\PokladniPohyb();
        $this->timerStart('Cash Move', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Cash Move', false);
        return $checker;
    }

    /**
     * 
     * @param \AbraFlexi\Cenik $identifier
     * 
     * @return \AbraFlexi\Cenik
     */
    function readPricelistItem($identifier) {
        $checker = new AbraFlexi\Cenik();
        $this->timerStart('Pricelist Item', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Pricelist Item', false);
        return $checker;
    }

    /**
     * Create & Read an invoice record
     * 
     * @param \AbraFlexi\FakturaVydana $identifier
     * 
     * @return \AbraFlexi\FakturaVydana
     */
    function readInvoice($identifier) {
        $checker = new AbraFlexi\FakturaVydana();
        $this->timerStart('Invoice', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Invoice', false);
        return $checker;
    }

    /**
     * Prepare Bank account
     * 
     * @param string $code
     * 
     * @return type
     */
    public function bankAccount($code = 'BENCHMARK') {
        $this->banka = new \AbraFlexi\RW(\AbraFlexi\RO::code($code), ['evidence' => 'bankovni-ucet', 'ignore404' => true]);
        if ($this->banka->lastResponseCode != 200) {
            $this->banka->sync(['kod' => $code, 'nazev' => $code]);
        }
        return $this->banka;
    }

    /**
     * Prepare cash move type for Testing
     * 
     * @param string $code
     * 
     * @return \AbraFlexi\RW
     */
    public function cashMoveType($code = 'BENCHMARK') {
        $this->cashType = new \AbraFlexi\RW(\AbraFlexi\RO::code($code), ['evidence' => 'typ-pokladni-pohyb', 'ignore404' => true]);
        if ($this->cashType->lastResponseCode != 200) {
            $this->cashType->sync(['kod' => $code, 'nazev' => $code]);
        }
        return $this->cashType;
    }

    /**
     * Prepare cash for Testing
     * 
     * @param string $code
     * 
     * @return \AbraFlexi\RW
     */
    public function cash($code = 'BENCHMARK') {
        $this->cash = new \AbraFlexi\RW(\AbraFlexi\RO::code($code), ['evidence' => 'pokladna', 'ignore404' => true]);
        if ($this->cash->lastResponseCode != 200) {
            $this->cash->sync(['kod' => $code, 'nazev' => $code]);
        }
        return $this->cash;
    }

    /**
     * Prepare pricelist test Record
     * 
     * @param string $code
     * 
     * @return \AbraFlexi\Cenik
     */
    public function pricelist($code = 'BENCHMARK') {
        $this->pricelist = new \AbraFlexi\Cenik('code:' . $code, ['ignore404' => true]);
        if ($this->pricelist->lastResponseCode != 200) {
            $this->pricelist->sync(['kod' => $code, 'nazev' => $code]);
        }
        return $this->pricelist;
    }

    /**
     * Prepare database for testing
     */
    public function prepare() {
        $this->bankAccount();
        $this->cashMoveType();
        $this->cash();
        $this->pricelist();
    }

    /**
     * Perform all probes
     */
    public function probeAll() {
        $allCycles = $this->cycles;
        do {
            $this->addStatusMessage('Pass: #' . ($allCycles - $this->cycles) . '/' . $allCycles, 'debug');
            $this->readAddress($this->createAddress());
            $this->readBankMove($this->createBankMove());
            $this->readCashMove($this->createCashMove());
            $this->readPricelistItem($this->createPricelistItem());
            $this->readInvoice($this->createInvoice());
        } while ($this->cycles--);
    }

    /**
     * Show final report
     */
    public function printResults() {
        $this->logBanner('cycles' . $this->cycles . ' with delay ' . $this->delay . 's.');
        echo vsprintf("%-30s; %-10s; %-10s\n", ['operation', 'read time', 'write time']);
        foreach ($this->benchmark as $passId => $pass) {
            echo "               Pass: " . $passId . "\n";
            foreach (array_keys($pass) as $testName) {
                $values['name'] = $testName;
                $values['read'] = $this->timerValue($this->benchmark[$passId][$testName]['read']);
                $values['write'] = $this->timerValue($this->benchmark[$passId][$testName]['write']);
                echo vsprintf("%-30s; %-10s\t; %s\n", $values);
            }
        }
    }

}

$shortopts = "c:d:v::p::";
$options = getopt($shortopts);

if (empty($options)) {
    echo "Perform benchmark of AbraFlexi server\n\n";
    echo "\nUsage:\n";
    echo $argv[0] . " [-p] [-c cycles] [-d delay] \n\n";
    echo "example: " . $argv[0] . " -c 10 -d 5\n\n";
    exit();
}

$prober = new Prober();

if (array_key_exists('v', $options)) {
    exit(0);
}

if (array_key_exists('d', $options)) {
    $prober->delay = intval($options['d']);
}

if (array_key_exists('c', $options)) {
    $prober->cycles = intval($options['c']);
}

if (array_key_exists('p', $options)) {
    $prober->prepare();
}

$prober->probeAll();
$prober->printResults();

