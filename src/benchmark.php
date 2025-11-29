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

use AbraFlexi\Adresar;
use AbraFlexi\Banka;
use AbraFlexi\Cenik;
use AbraFlexi\FakturaVydana;
use AbraFlexi\Functions as Functions2;
use AbraFlexi\Pokladna;
use AbraFlexi\PokladniPohyb;
use AbraFlexi\RW;
use Ease\Functions;
use Ease\Shared;
use Faker\Factory;

/**
 * This file is part of the Tools4AbraFlexi package.
 *
 * https://github.com/VitexSoftware/AbraFlexi-Tools
 *
 * (C) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require \dirname(__DIR__).'/vendor/autoload.php';

\define('EASE_APPNAME', 'AbraFlexi Benchmark');

Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], '../.env');

if (empty(Shared::cfg('ABRAFLEXI_URL'))) {
    echo "Please set up AbraFlexi client configuration environment: \n\n";
    echo "ABRAFLEXI_URL=https://demo.abraflexi.eu:5434\n";
    echo "ABRAFLEXI_PASSWORD=winstrom\n";
    echo "ABRAFLEXI_LOGIN=winstrom\n";
    echo "ABRAFLEXI_COMPANY=demo_de\n";

    exit(1);
}

class benchmark extends RW
{
    public array $benchmark = [];

    /**
     * Perform this cycles count.
     */
    public int $cycles = 1;

    /**
     * Seconds to wait before each test.
     */
    public int $delay = 0;
    private RW $banka;
    private $cashType;
    private $pricelist;

    /**
     * Benchmark version.
     */
    private string $version = '1.1';
    private Pokladna $cash;

    public function __construct($init = null, $options = [])
    {
        parent::__construct($init, $options);
        $this->logBanner('AbraFlexi Prober v'.$this->version);

        $bankCode = Functions2::code(Shared::cfg('ABRAFLEXI_BANK', 'BANKOVNÍ ÚČET'));
        $this->banka = new \AbraFlexi\RW($bankCode, ['autoload' => false]);
    }

    /**
     * @param string $timerName
     * @param bool   $writing   Is this inset type opration ?
     */
    public function timerStart($timerName, $writing = null): void
    {
        if (null === $writing) {
            $this->benchmark[$this->cycles][$timerName] = ['start' => microtime()];
        } else {
            $this->benchmark[$this->cycles][$timerName][$writing ? 'write' : 'read'] = ['start' => microtime()];
        }
    }

    /**
     * Count the time pass.
     *
     * @param string $timerName
     * @param bool   $writing
     */
    public function timerStop($timerName, $writing = null): void
    {
        if (null === $writing) {
            $this->benchmark[$this->cycles][$timerName]['end'] = microtime();
        } else {
            $this->benchmark[$this->cycles][$timerName][$writing ? 'write' : 'read']['end'] = microtime();
        }

        sleep($this->delay);
    }

    /**
     * @param array $startEnd
     */
    public function timerValue($startEnd): string
    {
        $time_start = explode(' ', $startEnd['start']);
        $time_end = explode(' ', $startEnd['end']);

        return number_format((int) $time_end[1] + (int) $time_end[0] - ((int) $time_start[1] + (int) $time_start[0]), 3);
    }

    /**
     * Testing address record.
     *
     * @return Adresar
     */
    public function createAddress()
    {
        $faker = Factory::create();
        $checker = new Adresar();
        $checker->setData(
            [
                'popis' => $faker->userName,
                'email' => $faker->email,
                'nazev' => $faker->firstName.' '.$faker->lastName,
                'mesto' => $faker->city,
                'ulice' => $faker->streetName,
                'tel' => $faker->phoneNumber,
                'stat' => Functions2::code($faker->countryCode),
            ],
        );

        $this->timerStart('Address', 'write');
        $checker->insertToAbraFlexi();
        $this->timerStop('Address', 'write');
        $checker->addStatusMessage($checker->getRecordIdent().': '.$checker->getDataValue('nazev'), ($checker->lastResponseCode === 201) ? 'success' : 'error');

        return $checker;
    }

    /**
     * Testing bank move.
     *
     * @return Banka
     */
    public function createBankMove()
    {
        $yesterday = new \AbraFlexi\Date();
        $yesterday->modify('-1 day');

        $bdata = [
            'kod' => 'Benchmark:'.time(),
            'banka' => $this->banka,
            'typPohybuK' => 'typPohybu.prijem',
            'popis' => 'AbraFlexi Benchmark record',
            'varSym' => Functions::randomNumber(1111, 9999),
            'specSym' => Functions::randomNumber(111, 999),
            'bezPolozek' => true,
            'datVyst' => $yesterday,
            'typDokl' => Functions2::code('STANDARD'),
        ];

        $checker = new Banka($bdata);
        $this->timerStart('Bank Move', true);
        $checker->insertToAbraFlexi();
        $this->timerStop('Bank Move', true);

        return $checker;
    }

    /**
     * @return PokladniPohyb
     */
    public function createCashMove()
    {
        $checker = new PokladniPohyb();
        $this->timerStart('Cash Move', true);
        $cashMove = [
            'cisDosle' => time(),
            'kod' => Functions2::code('CASH_'.time()),
            'typDokl' => $this->cashType,
            'pokladna' => $this->cash,
            'polozkyDokladu' => [
                'Nazev' => 'Test',
                'typPolozkyK' => 'typPolozky.obecny',
                'mnozMj' => 1,
                'cenaMj' => Functions::randomNumber(1111, 9999),
            ],
            'popis' => 'benchmark',
            'typPohybuK' => 'typPohybu.prijem',
            'datVyst' => new \AbraFlexi\Date(),
        ];
        $checker->insertToAbraFlexi($cashMove);
        $this->timerStop('Cash Move', true);

        return $checker;
    }

    /**
     * @return Cenik
     */
    public function createPricelistItem()
    {
        $checker = new Cenik();
        $this->timerStart('Pricelist Item', true);
        $pricelistItem = [
            'kod' => Functions2::code('PRICELIST_'.time()),
            'nazev' => (string) time(),
        ];
        $checker->insertToAbraFlexi($pricelistItem);
        $this->timerStop('Pricelist Item', true);

        return $checker;
    }

    /**
     * @return FakturaVydana
     */
    public function createInvoice()
    {
        $yesterday = new \AbraFlexi\Date();
        $yesterday->modify('-1 day');
        $testCode = 'TEST_'.time();

        $idata = [
            'kod' => $testCode,
            'varSym' => Functions::randomNumber(1111, 9999),
            'specSym' => Functions::randomNumber(111, 999),
            'bezPolozek' => true,
            'popis' => 'AbraFlexi Test invoice',
            'datVyst' => $yesterday,
            'typDokl' => Functions2::code('FAKTURA'),
        ];

        $checker = new FakturaVydana($idata);
        $this->timerStart('Invoice', true);
        $checker->insertToAbraFlexi();
        $this->timerStop('Invoice', true);

        return $checker;
    }

    /**
     * @param Adresar $identifier
     *
     * @return Adresar
     */
    public function readAddress($identifier)
    {
        $checker = new Adresar();
        $this->timerStart('Address', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Address', false);

        return $checker;
    }

    /**
     * @param Banka $identifier
     *
     * @return Banka
     */
    public function readBankMove($identifier)
    {
        $checker = new Banka();
        $this->timerStart('Bank Move', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Bank Move', false);

        return $checker;
    }

    /**
     * @param PokladniPohyb $identifier
     *
     * @return PokladniPohyb
     */
    public function readCashMove($identifier)
    {
        $checker = new PokladniPohyb();
        $this->timerStart('Cash Move', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Cash Move', false);

        return $checker;
    }

    /**
     * @param Cenik $identifier
     *
     * @return Cenik
     */
    public function readPricelistItem($identifier)
    {
        $checker = new Cenik();
        $this->timerStart('Pricelist Item', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Pricelist Item', false);

        return $checker;
    }

    /**
     * Create & Read an invoice record.
     *
     * @param FakturaVydana $identifier
     *
     * @return FakturaVydana
     */
    public function readInvoice($identifier)
    {
        $checker = new FakturaVydana();
        $this->timerStart('Invoice', false);
        $checker->loadFromAbraFlexi($identifier->getRecordIdent());
        $this->timerStop('Invoice', false);

        return $checker;
    }

    /**
     * Prepare Bank account.
     *
     * @param string $code
     */
    public function bankAccount($code = 'BENCHMARK'): RW
    {
        $this->banka = new RW(Functions2::code($code), ['evidence' => 'bankovni-ucet', 'ignore404' => true]);

        if ($this->banka->lastResponseCode !== 200) {
            $this->banka->sync(['kod' => $code, 'nazev' => $code]);
        }

        return $this->banka;
    }

    /**
     * Prepare cash move type for Testing.
     *
     * @param string $code
     *
     * @return RW
     */
    public function cashMoveType($code = 'BENCHMARK')
    {
        $this->cashType = new RW(Functions2::code($code), ['evidence' => 'typ-pokladni-pohyb', 'ignore404' => true]);

        if ($this->cashType->lastResponseCode !== 200) {
            $this->cashType->sync(['kod' => $code, 'nazev' => $code]);
        }

        return $this->cashType;
    }

    /**
     * Prepare cash for Testing.
     *
     * @param string $code
     *
     * @return RW
     */
    public function cash($code = 'BENCHMARK')
    {
        $this->cash = new Pokladna(Functions2::code($code), ['ignore404' => true]);

        if ($this->cash->lastResponseCode !== 200) {
            $this->cash->sync(['kod' => $code, 'nazev' => $code]);
        }

        return $this->cash;
    }

    /**
     * Prepare pricelist test Record.
     *
     * @param string $code
     *
     * @return Cenik
     */
    public function pricelist($code = 'BENCHMARK')
    {
        $this->pricelist = new Cenik('code:'.$code, ['ignore404' => true]);

        if ($this->pricelist->lastResponseCode !== 200) {
            $this->pricelist->sync(['kod' => $code, 'nazev' => $code]);
        }

        return $this->pricelist;
    }

    /**
     * Prepare database for testing.
     */
    public function prepare(): void
    {
        $this->bankAccount();
        $this->cashMoveType();
        $this->cash();
        $this->pricelist();
    }

    /**
     * Perform all probes.
     */
    public function probeAll(): void
    {
        $allCycles = $this->cycles;

        do {
            $this->addStatusMessage('Pass: #'.($allCycles - $this->cycles).'/'.$allCycles, 'debug');
            $this->readAddress($this->createAddress());
            $this->readBankMove($this->createBankMove());
            $this->readCashMove($this->createCashMove());
            $this->readPricelistItem($this->createPricelistItem());
            $this->readInvoice($this->createInvoice());
        } while ($this->cycles--);
    }

    /**
     * Show final report.
     */
    public function printResults(): void
    {
        $this->logBanner('cycles'.$this->cycles.' with delay '.$this->delay.'s.');
        echo vsprintf("%-30s; %-10s; %-10s\n", ['operation', 'read time', 'write time']);

        foreach ($this->benchmark as $passId => $pass) {
            echo '               Pass: '.$passId."\n";

            foreach (array_keys($pass) as $testName) {
                $values['name'] = $testName;
                $values['read'] = $this->timerValue($this->benchmark[$passId][$testName]['read']);
                $values['write'] = $this->timerValue($this->benchmark[$passId][$testName]['write']);
                echo vsprintf("%-30s; %-10s\t; %s\n", $values);
            }
        }
    }

    public function getReport(): array
    {
        $results = [];

        foreach ($this->benchmark as $passId => $pass) {
            $passResults = ['pass' => $passId, 'operations' => []];

            foreach (array_keys($pass) as $testName) {
                $operation = [
                    'name' => $testName,
                    'read' => $this->timerValue($this->benchmark[$passId][$testName]['read']),
                    'write' => $this->timerValue($this->benchmark[$passId][$testName]['write']),
                ];
                $passResults['operations'][] = $operation;
            }

            $results[] = $passResults;
        }

        return $results;
    }
}

$exitcode = 0;
$shortopts = 'c:d:v::p::o::e::';
$options = getopt($shortopts, ['output::environment::']);

if (empty($options)) {
    echo "Perform benchmark of AbraFlexi server\n\n";
    echo "\nUsage:\n";
    echo $argv[0]." [-p] [-c cycles] [-d delay] \n\n";
    echo 'example: '.$argv[0]." -c 10 -d 5\n\n";

    exit;
}

Shared::init(
    [
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
    ],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);

$destination = \array_key_exists('o', $options) ? $options['o'] : (\array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout'));

$prober = new benchmark();

if (\array_key_exists('v', $options)) {
    exit(0);
}

if (\array_key_exists('d', $options)) {
    $prober->delay = (int) $options['d'];
}

if (\array_key_exists('c', $options)) {
    $prober->cycles = (int) $options['c'];
}

if (\array_key_exists('p', $options)) {
    $prober->prepare();
}

$prober->probeAll();
$prober->printResults();

$prober->addStatusMessage('benchmark done', 'debug');

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE : 0));
$prober->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
