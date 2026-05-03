<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        return view('admin.finance.index', [
            'users' => User::where('is_admin', false)->latest()->paginate(12),
            'transactions' => Transaction::with('user')->latest()->take(20)->get(),
            'deposits' => Deposit::with('user')->latest()->take(20)->get(),
        ]);
    }

    public function adjustBalance(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $newBalance = max(0, $lockedUser->wallet_balance + $data['amount']);

            $lockedUser->update(['wallet_balance' => $newBalance]);

            Transaction::create([
                'user_id' => $lockedUser->id,
                'type' => $data['amount'] > 0 ? 'manual_credit' : 'manual_debit',
                'code' => 'ADMIN' . $lockedUser->id . now()->format('YmdHis'),
                'amount' => abs($data['amount']),
                'method' => 'admin',
                'status' => 'success',
                'confirmed_at' => now(),
                'meta' => ['note' => $data['note'] ?? null],
            ]);
        });

        return back()->with('success', 'Da cap nhat so du khach hang.');
    }
}
