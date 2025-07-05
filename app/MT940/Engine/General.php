<?php

namespace App\MT940\Engine;

//use Kingsquare\Banking\Statement;
use App\MT940\Statement;
use Illuminate\Support\Facades\Log;
use Kingsquare\Banking\Transaction;
use Kingsquare\Parser\Banking\Mt940;

class General extends Mt940\Engine
{
    const PATTERN_TAG_61 = '/^:61:(\d{6})(\d{4})?(C|D)[A-Z]?(\d+,?\d*)([FNS])?([A-Z]{3})?(.*)?/m';

    /**
     * returns the name of the bank.
     *
     * @return string
     */
    protected function parseStatementBank()
    {
        return 'General';
    }

    /**
     * Overloaded
     *
     * @return array
     */
    protected function parseStatementData(): array
    {
        // Remove the envelope block headers if present
        $rawData = $this->getRawData();

        // Remove everything before ":20:" and after "-"
        $rawData = preg_replace('/^\{1:.*?\}\{2:.*?\}\{4:|\-}$/s', '', $rawData);

        // Split each statement block starting with ":20:"
        $results = preg_split('/(?=\:20\:)/', trim($rawData), -1, PREG_SPLIT_NO_EMPTY);

        return $results;
    }

    /**
     * Overloaded: add support for statement and transaction parsing.
     *
     * @return Statement[]
     */
    public function parse()
    {
        $results = [];
        foreach ($this->parseStatementData() as $this->currentStatementData) {
            Log::info($this->currentStatementData);
            $statement = new Statement();
            if ($this->debug) {
                $statement->rawData = $this->currentStatementData;
            }
            $statement->setBank($this->parseStatementBank());
            $statement->setReferenceNumber($this->parseStatementReferenceNumber());
            $statement->setAccount($this->parseStatementAccount());
            $statement->setStartPrice($this->parseStatementStartPrice());
            $statement->setEndPrice($this->parseStatementEndPrice());
            $statement->setStartTimestamp($this->parseStatementStartTimestamp());
            $statement->setEndTimestamp($this->parseStatementEndTimestamp());
            $statement->setNumber($this->parseStatementNumber());
            $statement->setCurrency($this->parseStatementCurrency());

            foreach ($this->parseTransactionData() as $this->currentTransactionData) {
                $transaction = new Transaction();
                if ($this->debug) {
                    $transaction->rawData = $this->currentTransactionData;
                }
                $transaction->setAccount($this->parseTransactionAccount());
                $transaction->setAccountName($this->parseTransactionAccountName());
                $transaction->setPrice($this->parseTransactionPrice());
                $transaction->setDebitCredit($this->parseTransactionDebitCredit());
                $transaction->setDescription($this->parseTransactionDescription());
                $transaction->setTransactionCode($this->parseTransactionCode());
                $transaction->setValueTimestamp($this->parseTransactionValueTimestamp());
                $transaction->setEntryTimestamp($this->parseTransactionEntryTimestamp());
                $statement->addTransaction($transaction);
            }
            $results[] = $statement;
        }

        return $results;
    }

    /**
     * Overloaded: Extracts account number from the statement.
     *
     * @return string
     */
    protected function parseStatementAccount()
    {
        $results = [];
        if (preg_match('/:25:([0-9X]+)*/', $this->getCurrentStatementData(), $results)
            && !empty($results[1])
        ) {
            return $this->sanitizeAccount($results[1]);
        }

        return '';
    }

    /**
     * Overloaded: Extracts Reference number from the statement.
     *
     * @return string
     */
    protected function parseStatementReferenceNumber()
    {
        $data = $this->getCurrentStatementData();

        // Remove SWIFT headers (up to and including the {4: tag)
        $data = preg_replace('/^\{1:.*?\}\{2:.*?\}\{4:/s', '', $data);

        $results = [];
        // Match :20: followed by the reference, capturing until end of line
        if (preg_match('/:20:([^\r\n]+)/', $data, $results) && !empty($results[1])) {
            return trim($results[1]);
        }

        return '';
    }

    /**
     * Extracts transaction account info from the transaction data.
     *
     * @return string
     */
    protected function parseTransactionAccount()
    {
        $results = [];
        if (preg_match(self::PATTERN_TAG_61, $this->getCurrentTransactionData(), $results)) {
            return $this->sanitizeAccount($results[7]);
        }

        return '';
    }

    /**
     * Extracts debit/credit status for the transaction.
     *
     * @return string
     */
    protected function parseTransactionDebitCredit()
    {
        $results = [];
        if (preg_match(self::PATTERN_TAG_61, $this->getCurrentTransactionData(), $results)) {
            return $this->sanitizeDebitCredit($results[3]);
        }

        return '';
    }

    /**
     * Extracts transaction account name.
     *
     * @return string
     */
    protected function parseTransactionAccountName()
    {
        $results = [];
        if (preg_match('/:86:(.*)/', $this->getCurrentTransactionData(), $results)) {
            return $this->sanitizeAccountName($results[1]);
        }

        return '';
    }

    /**
     * Extracts description from the transaction.
     *
     * @return string
     */
    protected function parseTransactionDescription()
    {
        $results = [];
        if (preg_match('/:86:(.*)/', $this->getCurrentTransactionData(), $results)) {
            return $this->sanitizeDescription($results[1]);
        }

        return '';
    }

    /**
     * Extracts transaction value timestamp.
     *
     * @return int
     */
    protected function parseTransactionValueTimestamp()
    {
        $results = [];
        if (preg_match(self::PATTERN_TAG_61, $this->getCurrentTransactionData(), $results)) {
            return $this->sanitizeTimestamp($results[1]);
        }

        return 0;
    }

    /**
     * Extracts transaction entry timestamp.
     *
     * @return int
     */
    protected function parseTransactionEntryTimestamp()
    {
        $results = [];
        if (preg_match('/^:60F:C(\d{6})/m', $this->getCurrentStatementData(), $results)) {
            return $this->sanitizeTimestamp($results[1]);
        }

        return 0;
    }

    /**
     * Overloaded: Description sanitization specific to Danamon format.
     *
     * @param string $string
     * @return string
     */
    protected function sanitizeDescription($string)
    {
        // Custom sanitization logic for Bank Danamon
        return trim($string);
    }

    /**
     * Overloaded: Debit/Credit sanitization for Danamon format.
     *
     * @param string $string
     * @return string
     */
    protected function sanitizeDebitCredit($string)
    {
        $debitOrCredit = strtoupper(substr((string)$string, -1, 1));
        if ($debitOrCredit !== Transaction::DEBIT && $debitOrCredit !== Transaction::CREDIT) {
            trigger_error('wrong value for debit/credit (' . $string . ')', E_USER_ERROR);
            $debitOrCredit = '';
        }

        return $debitOrCredit;
    }

    /**
     * Overloaded: Is applicable if first line starts with :20:.
     *
     * @param string $string
     * @return bool
     */
    public static function isApplicable($string)
    {
        $firstline = strtok($string, "\r\n\t");

        return strpos($firstline, ':20:') !== false;
    }

    /**
     * Parse the full :86: transaction description line
     *
     * @param string $line86 The raw :86: line from MT940
     * @return array Parsed fields
     */
    public function parseFullTransactionDescription(string $line86): array
    {
        $result = [
            'transaction_code'     => null,
            'transaction_type'     => null,
            'internal_reference'   => null,
            'bank_reference'       => null,
            'payment_reference'    => null,
            'currency'             => null,
            'recipient'            => null,
        ];

        // Remove the ":86:" prefix if present
        $line86 = ltrim($line86, ':86:');

        // Split the first section by "/"
        $parts = explode('/', $line86, 4);

        if (count($parts) === 4) {
            $result['transaction_code']    = trim($parts[0]);
            $result['transaction_type']    = trim($parts[1]);
            $result['internal_reference']  = trim($parts[2]);

            // $parts[3] contains the rest of the string, including bank ref and trailing info
            $rest = trim($parts[3]);

            // Try to extract bank reference and trailing data
            if (preg_match('/^([^\s]+)\s+(.*)$/', $rest, $matches)) {
                $result['bank_reference'] = $matches[1];

                // Now extract payment reference, currency, and recipient
                if (preg_match('/(\d+)-([A-Z]{3})\s+(.+)/', $matches[2], $finalMatch)) {
                    $result['payment_reference'] = $finalMatch[1];
                    $result['currency']          = $finalMatch[2];
                    $result['recipient']         = trim($finalMatch[3]);
                }
            }
        }

        return $result;
    }
}
