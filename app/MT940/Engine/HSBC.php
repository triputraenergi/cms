<?php

namespace App\MT940\Engine;

use Kingsquare\Parser\Banking\Mt940\Engine;
use Kingsquare\Banking\Statement;

class HSBC extends Engine
{
    protected function parseStatementLine(string $transactionLine): Statement
    {
        $entry = new Statement();

        // Parse Tag 61
        if (preg_match('/:61:(\d{6})(\d{4})?([DC])(\d+,\d{0,2})(\w{4})([^\/]*)\/\/?([^\n\r]*)/s', $transactionLine, $matches)) {
            $entry->setValueDate($this->parseDate($matches[1]));
            $entry->setEntryDate($this->parseDate($matches[2] ?? ''));
            $entry->setDebitCredit($matches[3]);
            $entry->setAmount(str_replace(',', '.', $matches[4]));
            $entry->setTransactionType($matches[5]);
            $entry->setTransactionReference($matches[6]);
            $entry->setBankReference($matches[7]);
        }

        // Parse Tag 86
        if (preg_match('/:86:(.*?)\n/s', $transactionLine, $matches)) {
            $entry->setDescription(trim(preg_replace('/\s+/', ' ', $matches[1])));
        }

        return $entry;
    }

    protected function parseDate(string $date): string
    {
        if (strlen($date) === 6) {
            return \DateTime::createFromFormat('ymd', $date)->format('Y-m-d');
        }
        if (strlen($date) === 4) {
            return \DateTime::createFromFormat('md', $date)->format('Y-m-d');
        }
        return '';
    }

    public function identify(string $text): bool
    {
        // Basic check for HSBC Indonesia MT940 file
        return strpos($text, 'HSBC') !== false && strpos($text, ':61:') !== false && strpos($text, ':86:') !== false;
    }
}
