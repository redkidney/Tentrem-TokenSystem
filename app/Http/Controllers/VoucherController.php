<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function create()
    {
        $vouchers = Voucher::all();
        return view('create_vouchers', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'voucher_name' => 'required|string|max:10',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        Voucher::create($validated);

        return redirect()->route('vouchers.create')->with('success', 'Voucher created successfully!');
    }
}
