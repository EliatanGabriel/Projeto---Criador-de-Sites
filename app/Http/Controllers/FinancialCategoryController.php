<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialCategoryRequest;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialCategoryController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('finance/categories', [
            'categories' => FinancialCategory::orderBy('type')->orderBy('name')->get(),
            'canManage' => $request->user()->can('finance.manage'),
        ]);
    }

    public function store(StoreFinancialCategoryRequest $request)
    {
        FinancialCategory::create($request->validated());

        return back()->with('success', 'Categoria criada.');
    }
}
