<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use App\MT940\Engine\General;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Kingsquare\Parser\Banking\Mt940;


class BankStatementController extends Controller
{
    public function showUploadForm()
    {
        return view('bank.upload');
    }

    public function handleUpload(Request $request): \Illuminate\Http\JsonResponse
    {
        $fileContent = file_get_contents($request->file('file')->getPathname());

        // Initialize the MT940Parser
        $parser = new Mt940();


        try {
            $statements = $parser->parse($fileContent);
            $result = [];

            foreach ($statements as $i => $statement) {
                // Create the BankStatement record
                $bankStatementData = [
//                    'reference' => $statement->get(),  // :20:
                    'account_id' => $statement->getAccount(),   // :25:
                    'statement_number' => $statement->getNumber(), // :28C:
                    'opening_balance' => $statement->getStartPrice(), // :60F:
                    'closing_balance' => $statement->getEndPrice(), // :60F:
                    'currency' => $statement->getCurrency(),   // Currency from :60F
                    'opening_balance_date' => Date::createFromTimestamp($statement->getStartTimestamp()), // Date from :60F
                ];


                Log::info('Bank Statement ' . $i+1, $bankStatementData);

//                $bankStatement = BankStatement::create($bankStatementData);

                // Loop through transactions and create BankTransaction records
                foreach ($statement->getTransactions() as $index => $transaction) {
                    $transactionData = [
//                        'bank_statement_id' => $bankStatement->id,
                        'transaction_date' => $transaction->getEntryTimestamp(), // :61:
                        'type' => $transaction->getDebitCredit(),   // 'D' or 'C'
                        'amount' => $transaction->getPrice(), // Amount from :61:
                        'reference' => $transaction->getTransactionCode(), // Reference from :61:
                        'description' => $transaction->getDescription(), // Description from :86:
                    ];
                    Log::info('transaction ' . $index+1, $transactionData);
//                    BankTransaction::create();
                }

            }

            return response()->json(['success' => true, 'data' => $statements]);

        } catch (\Exception $e) {
            Log::error('Error parsing MT940 file', ['exception' => $e]);
            return response()->json(['error' => 'Failed to parse MT940 file'], 400);
        }
    }
}
