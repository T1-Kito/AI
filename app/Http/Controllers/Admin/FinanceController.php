<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->string('q')),
            'status' => (string) $request->string('status'),
            'range' => (string) $request->string('range'),
            'tx_status' => (string) $request->string('tx_status'),
            'tx_range' => (string) $request->string('tx_range'),
        ];

        $usersQuery = User::query()->where('is_admin', false);

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $usersQuery->where(function ($query) use ($keyword): void {
                $query->where('email', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', '%' . $keyword . '%');
            });
        }

        if ($filters['status'] === 'has_balance') {
            $usersQuery->where('wallet_balance', '>', 0);
        } elseif ($filters['status'] === 'zero_balance') {
            $usersQuery->where('wallet_balance', '=', 0);
        }

        $fromDate = $this->resolveRangeDate($filters['range']);
        if ($fromDate) {
            $usersQuery->where('created_at', '>=', $fromDate);
        }

        $users = $usersQuery->latest()->paginate(12)->withQueryString();

        $transactionsQuery = Transaction::with('user')->latest();

        if ($filters['tx_status'] !== '') {
            $transactionsQuery->where('status', $filters['tx_status']);
        }

        $txFromDate = $this->resolveRangeDate($filters['tx_range']);
        if ($txFromDate) {
            $transactionsQuery->where('created_at', '>=', $txFromDate);
        }

        $transactions = $transactionsQuery->take(80)->get();

        return view('admin.finance.index', [
            'users' => $users,
            'transactions' => $transactions,
            'filters' => $filters,
            'stats' => [
                'total_balance' => (int) User::where('is_admin', false)->sum('wallet_balance'),
                'monthly_deposit_total' => (int) Deposit::where('status', 'success')
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('amount'),
                'monthly_deposit_count' => (int) Deposit::where('status', 'success')
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'new_customers_count' => (int) User::where('is_admin', false)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'pending_transactions_count' => (int) Transaction::where('status', 'pending')->count(),
            ],
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

    private function resolveRangeDate(string $range): ?Carbon
    {
        return match ($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            'month' => now()->startOfMonth(),
            default => null,
        };
    }
}
