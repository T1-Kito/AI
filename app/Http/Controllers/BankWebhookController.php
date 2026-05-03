<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            hash_equals((string) config('services.bank_webhook.token'), (string) $request->header('X-Bank-Webhook-Token')),
            403
        );

        $data = $request->validate([
            'transaction_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:1000'],
            'bank_ref' => ['nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:255'],
        ]);

        $data['amount'] = (int) $data['amount'];
        $description = Str::upper($data['description']);

        if (! preg_match('/NAPTIEN_USER(\d+)/', $description, $matches)) {
            $this->logBankingError('invalid_memo_format', $data, null);

            return response()->json([
                'success' => false,
                'error' => 'Invalid transfer memo format',
            ], 422);
        }

        $userId = (int) $matches[1];
        $minDeposit = (int) config('services.bank_webhook.min_deposit', 20000);

        $result = DB::transaction(function () use ($data, $userId, $minDeposit): array {
            $existingTransaction = Transaction::where('code', $data['transaction_id'])
                ->where('type', 'topup')
                ->lockForUpdate()
                ->first();

            if ($existingTransaction) {
                return [
                    'success' => true,
                    'credited' => false,
                    'message' => 'Bank transaction already processed',
                ];
            }

            $user = User::whereKey($userId)->lockForUpdate()->first();

            if (! $user) {
                $this->logBankingError('user_not_found', $data, $userId);

                return [
                    'success' => false,
                    'error' => 'User not found from transfer memo',
                ];
            }

            $user->wallet_balance += $data['amount'];
            $user->save();

            $warning = $data['amount'] < $minDeposit
                ? 'Deposit amount is lower than MIN_DEPOSIT'
                : null;

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'code' => $data['transaction_id'],
                'amount' => $data['amount'],
                'method' => 'bank',
                'status' => 'completed',
                'confirmed_at' => now(),
                'meta' => [
                    'description' => $data['description'],
                    'bank_ref' => $data['bank_ref'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                    'memo' => 'NAPTIEN_USER' . $user->id,
                    'warning' => $warning,
                ],
            ]);

            Deposit::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'fee' => 0,
                'code' => $data['transaction_id'],
                'method' => 'bank',
                'status' => 'completed',
            ]);

            if ($warning) {
                Log::warning('Bank deposit lower than minimum', [
                    'user_id' => $user->id,
                    'transaction_id' => $data['transaction_id'],
                    'amount' => $data['amount'],
                    'min_deposit' => $minDeposit,
                ]);
            }

            return [
                'success' => true,
                'credited' => true,
                'transaction_id' => $transaction->code,
                'warning' => $warning,
            ];
        });

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function logBankingError(string $reason, array $payload, ?int $userId): void
    {
        DB::table('logs_error_banking')->insert([
            'user_id' => $userId,
            'reason' => $reason,
            'transaction_id' => $payload['transaction_id'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'description' => $payload['description'] ?? null,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
