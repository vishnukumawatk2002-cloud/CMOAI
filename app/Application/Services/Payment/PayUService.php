<?php

namespace App\Application\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayUService
{
    public function isConfigured(): bool
    {
        return filled(config('services.payu.merchant_key'))
            && filled(config('services.payu.merchant_salt'))
            && filled(config('services.payu.base_url'));
    }

    /** @param array<string, string> $data */
    public function buildPaymentRequest(array $data): array
    {
        $key = (string) config('services.payu.merchant_key');
        $salt = (string) config('services.payu.merchant_salt');

        $txnid = $data['txnid'];
        $amount = $data['amount'];
        $productinfo = $data['productinfo'];
        $firstname = $data['firstname'];
        $email = $data['email'];
        $phone = $data['phone'] ?? '9999999999';
        $udf1 = $data['udf1'] ?? '';
        $udf2 = $data['udf2'] ?? '';
        $udf3 = $data['udf3'] ?? '';
        $udf4 = $data['udf4'] ?? '';
        $udf5 = $data['udf5'] ?? '';

        return [
            'key' => $key,
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productinfo,
            'firstname' => $firstname,
            'email' => $email,
            'phone' => $phone,
            'surl' => $data['surl'],
            'furl' => $data['furl'],
            'udf1' => $udf1,
            'udf2' => $udf2,
            'udf3' => $udf3,
            'udf4' => $udf4,
            'udf5' => $udf5,
            'hash' => $this->generateRequestHash(
                $key,
                $salt,
                $txnid,
                $amount,
                $productinfo,
                $firstname,
                $email,
                $udf1,
                $udf2,
                $udf3,
                $udf4,
                $udf5,
            ),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function verifyResponse(array $payload): bool
    {
        $postedHash = strtolower(trim((string) ($payload['hash'] ?? '')));

        if ($postedHash === '') {
            Log::warning('PayU verify failed: empty hash', [
                'keys' => array_keys($payload),
                'status' => $payload['status'] ?? null,
                'txnid' => $payload['txnid'] ?? null,
            ]);

            return $this->verifyPaymentStatusViaApi((string) ($payload['txnid'] ?? ''));
        }

        foreach ($this->merchantSalts() as $salt) {
            foreach ($this->reverseHashCandidates($payload, $salt) as $calculated) {
                if (hash_equals($calculated, $postedHash)) {
                    return true;
                }
            }
        }

        Log::warning('PayU verify failed: hash mismatch', [
            'status' => $payload['status'] ?? null,
            'txnid' => $payload['txnid'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'email' => $payload['email'] ?? null,
            'firstname' => $payload['firstname'] ?? null,
            'productinfo' => $payload['productinfo'] ?? null,
            'udf1' => $payload['udf1'] ?? null,
            'udf2' => $payload['udf2'] ?? null,
            'udf3' => $payload['udf3'] ?? null,
            'key' => $payload['key'] ?? null,
            'posted_hash_prefix' => substr($postedHash, 0, 12),
        ]);

        // Fallback: ask PayU server directly (handles salt version mismatches).
        return $this->verifyPaymentStatusViaApi((string) ($payload['txnid'] ?? ''));
    }

    /**
     * Confirm txn status with PayU verify_payment command.
     */
    public function verifyPaymentStatusViaApi(string $txnid): bool
    {
        $txnid = trim($txnid);

        if ($txnid === '') {
            return false;
        }

        $key = (string) config('services.payu.merchant_key');
        $command = 'verify_payment';
        $endpoint = $this->merchantApiEndpoint();

        foreach ($this->merchantSalts() as $salt) {
            $hash = strtolower(hash('sha512', $key.'|'.$command.'|'.$txnid.'|'.$salt));

            try {
                $response = Http::asForm()
                    ->timeout(20)
                    ->post($endpoint, [
                        'key' => $key,
                        'command' => $command,
                        'var1' => $txnid,
                        'hash' => $hash,
                    ]);
            } catch (\Throwable $e) {
                Log::warning('PayU verify_payment request failed', [
                    'txnid' => $txnid,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $body = $response->json();

            if (! is_array($body)) {
                // Sometimes PayU returns serialized PHP / plain text.
                $raw = $response->body();
                if (stripos($raw, 'status=success') !== false || stripos($raw, '"status":"success"') !== false) {
                    return true;
                }

                continue;
            }

            $transaction = data_get($body, 'transaction_details.'.$txnid);

            if (! is_array($transaction)) {
                $transaction = data_get($body, 'transaction_details');
                if (is_array($transaction) && isset($transaction['status'])) {
                    // single txn shape
                } elseif (is_array($transaction)) {
                    $transaction = reset($transaction) ?: null;
                }
            }

            $status = strtolower((string) data_get($transaction, 'status', data_get($body, 'status', '')));

            if (in_array($status, ['success', 'captured'], true)) {
                return true;
            }

            Log::warning('PayU verify_payment not success', [
                'txnid' => $txnid,
                'status' => $status,
                'body_status' => $body['status'] ?? null,
            ]);
        }

        return false;
    }

    /** @return list<string> */
    private function merchantSalts(): array
    {
        $salts = [
            (string) config('services.payu.merchant_salt'),
            (string) config('services.payu.merchant_salt_v1'),
        ];

        return array_values(array_unique(array_filter($salts, fn ($salt) => filled($salt))));
    }

    private function merchantApiEndpoint(): string
    {
        $base = (string) config('services.payu.base_url');

        if (str_contains($base, 'test.payu.in')) {
            return 'https://test.payu.in/merchant/postservice.php?form=2';
        }

        return 'https://info.payu.in/merchant/postservice.php?form=2';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function reverseHashCandidates(array $payload, string $salt): array
    {
        $key = (string) ($payload['key'] ?? config('services.payu.merchant_key'));
        $status = (string) ($payload['status'] ?? '');
        $txnid = (string) ($payload['txnid'] ?? '');
        $productinfo = (string) ($payload['productinfo'] ?? '');
        $firstname = (string) ($payload['firstname'] ?? '');
        $email = (string) ($payload['email'] ?? '');
        $udf1 = (string) ($payload['udf1'] ?? '');
        $udf2 = (string) ($payload['udf2'] ?? '');
        $udf3 = (string) ($payload['udf3'] ?? '');
        $udf4 = (string) ($payload['udf4'] ?? '');
        $udf5 = (string) ($payload['udf5'] ?? '');
        $additionalCharges = trim((string) (
            $payload['additionalCharges']
            ?? $payload['additional_charges']
            ?? ''
        ));

        $amounts = array_values(array_unique(array_filter([
            (string) ($payload['amount'] ?? ''),
            $this->normalizeAmount((string) ($payload['amount'] ?? '')),
        ])));

        $udfSets = [
            [$udf1, $udf2, $udf3, $udf4, $udf5],
            ['', '', '', '', ''],
        ];

        $hashes = [];

        foreach ($amounts as $amount) {
            foreach ($udfSets as [$u1, $u2, $u3, $u4, $u5]) {
                $withUdf = [
                    $salt,
                    $status,
                    '',
                    '',
                    '',
                    '',
                    '',
                    $u5,
                    $u4,
                    $u3,
                    $u2,
                    $u1,
                    $email,
                    $firstname,
                    $productinfo,
                    $amount,
                    $txnid,
                    $key,
                ];
                $hashes[] = strtolower(hash('sha512', implode('|', $withUdf)));

                if ($additionalCharges !== '') {
                    $hashes[] = strtolower(hash('sha512', implode('|', array_merge([$additionalCharges], $withUdf))));
                }
            }

            $legacy = [
                $salt,
                $status,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $email,
                $firstname,
                $productinfo,
                $amount,
                $txnid,
                $key,
            ];
            $hashes[] = strtolower(hash('sha512', implode('|', $legacy)));

            if ($additionalCharges !== '') {
                $hashes[] = strtolower(hash('sha512', implode('|', array_merge([$additionalCharges], $legacy))));
            }
        }

        return array_values(array_unique($hashes));
    }

    private function normalizeAmount(string $amount): string
    {
        if ($amount === '' || ! is_numeric($amount)) {
            return $amount;
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function generateRequestHash(
        string $key,
        string $salt,
        string $txnid,
        string $amount,
        string $productinfo,
        string $firstname,
        string $email,
        string $udf1,
        string $udf2,
        string $udf3,
        string $udf4,
        string $udf5,
    ): string {
        $sequence = implode('|', [
            $key,
            $txnid,
            $amount,
            $productinfo,
            $firstname,
            $email,
            $udf1,
            $udf2,
            $udf3,
            $udf4,
            $udf5,
            '',
            '',
            '',
            '',
            '',
            $salt,
        ]);

        return strtolower(hash('sha512', $sequence));
    }
}
