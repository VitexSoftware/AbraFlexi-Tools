<?php

$loaderPath = realpath(__DIR__ . "/../../../autoload.php");
if (file_exists($loaderPath)) {
    require $loaderPath;
} else {
    require __DIR__ . '/../vendor/autoload.php';
}

define('EASE_APPNAME', 'FlexiBee Benchmark');
define('EASE_LOGGER', 'syslog|console');

if (empty(getenv('FLEXIBEE_URL'))) {
    echo "Please set up FlexiBee client configuration environment: \n\n";
    echo "FLEXIBEE_URL=https://demo.flexibee.eu:5434\n";
    echo "FLEXIBEE_PASSWORD=winstrom\n";
    echo "FLEXIBEE_LOGIN=winstrom\n";
    echo "FLEXIBEE_COMPANY=demo_de\n";
}

class Prober extends \FlexiPeeHP\FlexiBeeRW {

    /**
     *
     * @var array 
     */
    public $benchmark = [];

    public function __construct($init = null, $options = array()) {
        parent::__construct($init, $options);
        $this->logBanner('FlexiBee Prober');
    }

    /**
     * 
     * @param string $timerName
     * @param boolean $writing Is this inset type opration ?          
     */
    function timerStart($timerName, $writing = null) {
        if (is_null($writing)) {
            $this->benchmark[$timerName] = ['start' => microtime()];
        } else {
            $this->benchmark[$timerName][$writing ? 'write' : 'read'] = ['start' => microtime()];
        }
    }

    /**
     * 
     * @param string $timerName
     * @param boolean $writing           
     */
    function timerStop($timerName, $writing = null) {
        if (is_null($writing)) {
            $this->benchmark[$timerName]['end'] = microtime();
        } else {
            $this->benchmark[$timerName][$writing ? 'write' : 'read']['end'] = microtime();
        }
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
     * 
     * @return \FlexiPeeHP\Adresar
     */
    function createAddress() {
        $faker = Faker\Factory::create();
        $checker = new FlexiPeeHP\Adresar();
        $checker->setData(
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

        $this->timerStart('Address', 'write');
        $checker->insertToFlexiBee();
        $this->timerStop('Address', 'write');
        $checker->addStatusMessage($checker->getRecordIdent() . ': ' . $checker->getDataValue('nazev'), ($checker->lastResponseCode == 201) ? 'success' : 'error');
        return $checker;
    }

    /**
     * 
     * @return \FlexiPeeHP\Banka
     */
    function createBankMove() {
        $yesterday = new \DateTime();
        $yesterday->modify('-1 day');

        $bdata = [
            'kod' => 'Benchmark:' . time(),
            'banka' => 'code:HLAVNI',
            'typPohybuK' => 'typPohybu.prijem',
            'popis' => 'FlexiBee Benchmark record',
            'varSym' => \Ease\Functions::randomNumber(1111, 9999),
            'specSym' => \Ease\Functions::randomNumber(111, 999),
            'bezPolozek' => true,
            'datVyst' => \FlexiPeeHP\FlexiBeeRO::dateToFlexiDate($yesterday),
            'typDokl' => \FlexiPeeHP\FlexiBeeRO::code('STANDARD')
        ];

        $checker = new FlexiPeeHP\Banka($bdata);
        $this->timerStart('Bank Move', true);
        $checker->insertToFlexiBee();
        $this->timerStop('Bank Move', true);
        return $checker;
    }

    /**
     * 
     * @return \FlexiPeeHP\PokladniPohyb
     */
    function createCashMove() {
        $checker = new FlexiPeeHP\PokladniPohyb();
        $this->timerStart('Cash Move', true);
        $checker->insertToFlexiBee();
        $this->timerStop('Cash Move', true);
        return $checker;
    }

    /**
     * 
     * @return \FlexiPeeHP\Cenik
     */
    function createPricelistItem() {
        $checker = new FlexiPeeHP\Cenik();
        $this->timerStart('Pricelist Item', true);
        $checker->insertToFlexiBee();
        $this->timerStop('Pricelist Item', true);
        return $checker;
    }

    /**
     * 
     * @return \FlexiPeeHP\FakturaVydana
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
            'popis' => 'FlexiPeeHP Test invoice',
            'datVyst' => \FlexiPeeHP\FlexiBeeRO::dateToFlexiDate($yesterday),
            'typDokl' => \FlexiPeeHP\FlexiBeeRO::code('FAKTURA')
        ];

        $checker = new FlexiPeeHP\FakturaVydana($idata);
        $this->timerStart('Invoice', true);
        $checker->insertToFlexiBee();
        $this->timerStop('Invoice', true);
        return $checker;
    }

    /**
     * 
     * @param \FlexiPeeHP\Adresar $identifier
     * 
     * @return \FlexiPeeHP\Adresar
     */
    function readAddress($identifier) {
        $checker = new FlexiPeeHP\Adresar();
        $this->timerStart('Address', false);
        $checker->loadFromFlexiBee($identifier->getRecordIdent());
        $this->timerStop('Address', false);
        return $checker;
    }

    /**
     * 
     * @param \FlexiPeeHP\Banka $identifier
     * 
     * @return \FlexiPeeHP\Banka
     */
    function readBankMove($identifier) {
        $checker = new FlexiPeeHP\Banka();
        $this->timerStart('Bank Move', false);
        $checker->loadFromFlexiBee($identifier->getRecordIdent());
        $this->timerStop('Bank Move', false);
        return $checker;
    }

    /**
     * 
     * @param \FlexiPeeHP\PokladniPohyb $identifier
     * 
     * @return \FlexiPeeHP\PokladniPohyb
     */
    function readCashMove($identifier) {
        $checker = new FlexiPeeHP\PokladniPohyb();
        $this->timerStart('Cash Move', false);
        $checker->loadFromFlexiBee($identifier->getRecordIdent());
        $this->timerStop('Cash Move', false);
        return $checker;
    }

    /**
     * 
     * @param \FlexiPeeHP\Cenik $identifier
     * 
     * @return \FlexiPeeHP\Cenik
     */
    function readPricelistItem($identifier) {
        $checker = new FlexiPeeHP\Cenik();
        $this->timerStart('Pricelist Item', false);
        $checker->loadFromFlexiBee($identifier->getRecordIdent());
        $this->timerStop('Pricelist Item', false);
        return $checker;
    }

    /**
     * 
     * @param \FlexiPeeHP\FakturaVydana $identifier
     * 
     * @return \FlexiPeeHP\FakturaVydana
     */
    function readInvoice($identifier) {
        $checker = new FlexiPeeHP\FakturaVydana();
        $this->timerStart('Invoice', false);
        $checker->loadFromFlexiBee($identifier->getRecordIdent());
        $this->timerStop('Invoice', false);
        return $checker;
    }

    public function probeAll() {
        $this->readAddress($this->createAddress());
        $this->readBankMove($this->createBankMove());
        $this->readCashMove($this->createCashMove());
        $this->readPricelistItem($this->createPricelistItem());
        $this->readInvoice($this->createInvoice());
    }

    public function printResults() {
        echo  vsprintf("%-30s; %s; %s\n",['operation' ,'read time'  ,'write time']);
        foreach (array_keys($this->benchmark) as $testName) {
            $values['name'] = $testName;
            $values['read'] = $this->timerValue($this->benchmark[$testName]['read']);
            $values['write'] = $this->timerValue($this->benchmark[$testName]['write']);
            echo  vsprintf("%-30s; %s\t; %s\n",$values);
        }
    }

}

$prober = new Prober();
$prober->probeAll();
$prober->printResults();


