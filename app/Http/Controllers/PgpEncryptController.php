<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenPGP;
use OpenPGP_Message;
use OpenPGP_Crypt_Symmetric;
use OpenPGP_Crypt_RSA;

class PgpEncryptController extends Controller
{
    public function handlePgp(Request $request)
    {
        $plaintext = 'Hello, this message will be signed and encrypted.';
        // Load recipient's public key (for encryption)
        $publicKeyArmored = file_get_contents(storage_path('pgp/keys/hsbc-public.key'));
        $publicKey = OpenPGP_Message::parse(OpenPGP::unarmor($publicKeyArmored, 'PGP PUBLIC KEY BLOCK'));

        // Load your private key (for signing)
        $privateKeyArmored = file_get_contents(storage_path('pgp/keys/client-private.key'));
        Log::info($privateKeyArmored);
        $privateKeyMsg = OpenPGP_Message::parse(OpenPGP::unarmor($privateKeyArmored, 'PGP PRIVATE KEY BLOCK'));

        // Decrypt your private key using passphrase
        $packets = $privateKeyMsg->packets;
        $privateKeyPacket = $packets[0];
        $privateKeyPacket->decrypt('1password'); // This works on packet, not the message

        // Create plaintext packet
        $dataPacket = new \OpenPGP_LiteralDataPacket($plaintext);

        // Create a signer instance and sign the message
        $signer = new OpenPGP_Crypt_RSA($privateKeyPacket);
        $signed = $signer->sign($dataPacket);

        // Encrypt the signed message using recipient's public key
        $encryptor = new OpenPGP_Crypt_RSA($publicKey);
        $encrypted = $encryptor->encrypt($signed);

        // ASCII armor the result
        $armored = OpenPGP::enarmor($encrypted->to_bytes(), 'PGP MESSAGE');

        return response($armored, 200, ['Content-Type' => 'text/plain']);
    }
}
