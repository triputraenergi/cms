<?php

namespace App\Http\Controllers;

use App\Services\PgpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class Controller
{

//    public function sendSecureMessage(Request $request)
//    {
//
//
//        $data = [
//            "transactionDate"=> "YYYY-MM-DD",
//            "transactionTimeFrom"=> "12=>56",
//            "transactionTimeTo"=> "18=>56",
//            "accountNumber"=> "1234576789",
//            "accountCountry"=> "GB",
//            "institutionCode"=> "HBEU",
//            "accountType"=> "CA",
//            "bankTransactionType"=> "SWIFT",
//            "enrichedReport"=> "E"
//        ];
//
//        $jsonMessage = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
//        $encrypted = $this->pgp->encryptAndSign(
//            $jsonMessage,
//            'hsbc-public.key',
//            'client-private.key',
//            'it-tem@triputraenergi.com', // must match UID in private key
//            '1password'
//        );
//
//        Log::info($encrypted);
//
//        return $encrypted;
//    }
}
