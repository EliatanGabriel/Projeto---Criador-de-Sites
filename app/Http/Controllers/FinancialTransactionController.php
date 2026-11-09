<?php

namespace App\Http\Controllers;

use App\Domain\Finance\Actions\CancelFinancialTransactionAction;
use App\Domain\Finance\Actions\RegisterFinancialTransactionAction;
use App\Domain\Shared\UserScope;
use App\Domain\Finance\Actions\RegisterPaymentAction;
use App\Http\Requests\StoreFinancialTransactionRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Client;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialTransaction::query()->with(['category', 'client:id,name', 'project:id,title'])->orderByDesc('created_at');
        $user = $request->user();
        UserScope::financialByClient($query, $user);

        if ($request->month) {
            $request->validate(['month' => 'date_format:Y-m']);
            $start = \Carbon\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
            $query->whereBetween('due_at', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        }
        if ($request->type) $query->where('type', $request->type);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('description', 'like', "%{$s}%")->orWhereHas('client', fn ($qq) => $qq->where('name', 'like', "%{$s}%")));
        }

        return Inertia::render('finance/index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['month', 'type', 'status', 'search']),
            'canManage' => auth()->user()->can('finance.manage'),
        ]);
    }

    public function create()
    {
        return Inertia::render('finance/create', [
            'categories' => FinancialCategory::all(),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderByDesc('created_at')->limit(100)->get(['id', 'title', 'client_id']),
        ]);
    }

    public function store(StoreFinancialTransactionRequest $request, RegisterFinancialTransactionAction $action)
    {
        $this->authorize('create', FinancialTransaction::class);
        $transaction = $action($request->validated());

        return redirect()->route('finance.show', $transaction)->with('success', 'Lançamento criado.');
    }

    public function show(FinancialTransaction $transaction)
    {
        $this->authorize('view', $transaction);
        $transaction->load(['category', 'client:id,name', 'project:id,title', 'payments', 'expenseRecords']);

        return Inertia::render('finance/show', [
            'transaction' => $transaction,
            'canManage' => auth()->user()->can('finance.manage'),
        ]);
    }

    public function payment(StorePaymentRequest $request, FinancialTransaction $transaction, RegisterPaymentAction $action)
    {
        $this->authorize('update', $transaction);
        $payment = $action($transaction, $request->validated());

        return back()->with('success', 'Pagamento registrado.');
    }

    public function cancel(FinancialTransaction $transaction, CancelFinancialTransactionAction $action)
    {
        $this->authorize('update', $transaction);
        $action($transaction);

        return back()->with('success', 'Lançamento cancelado.');
    }
}
