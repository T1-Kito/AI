<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function topup(): View
    {
        abort_if(auth()->user()->is_admin, 403);

        $suggestedAmount = 20000;
        $bankAccount = config('services.bank_account');

        return view('wallet.topup', [
            'deposits' => Deposit::whereBelongsTo(auth()->user())->latest()->paginate(10),
            'suggestedAmount' => $suggestedAmount,
            'bankAccount' => $bankAccount,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_if($request->user()->is_admin, 403);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:50000000'],
            'method' => ['required', 'in:bank,crypto'],
        ]);

        $payload = DB::transaction(function () use ($data): array {
            $user = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $fee = 0;
            $code = 'NAPTIEN_USER' . $user->id . '_' . now()->format('YmdHis');
            $bankAccount = config('services.bank_account');

            $deposit = Deposit::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'fee' => $fee,
                'code' => $code,
                'method' => $data['method'],
                'status' => 'pending',
            ]);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'code' => $code,
                'amount' => $data['amount'] - $fee,
                'method' => $data['method'],
                'status' => 'pending',
            ]);

            $qrUrl = sprintf(
                'https://img.vietqr.io/image/%s-%s-compact2.jpg?amount=%s&addInfo=%s&accountName=%s',
                rawurlencode($bankAccount['bank_code']),
                rawurlencode($bankAccount['account_number']),
                $data['amount'],
                rawurlencode($code),
                rawurlencode($bankAccount['account_name'])
            );

            return [
                'deposit_id' => $deposit->id,
                'code' => $code,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'bank_account' => [
                    'bank_name' => $bankAccount['bank_name'],
                    'bank_code' => $bankAccount['bank_code'],
                    'account_number' => $bankAccount['account_number'],
                    'account_name' => $bankAccount['account_name'],
                ],
                'qr_url' => $qrUrl,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Da tao giao dich nap tien',
            'data' => $payload,
        ]);
    }

    public function status(Deposit $deposit): JsonResponse
    {
        abort_if(auth()->user()->is_admin, 403);
        abort_unless($deposit->user_id === auth()->id(), 403);

        return response()->json([
            'success' => true,
            'status' => $deposit->status,
            'is_completed' => $deposit->status === 'completed',
        ]);
    }
}
