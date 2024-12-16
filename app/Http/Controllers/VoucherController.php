<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::all();
        return view('admin.create_vouchers', compact('vouchers'));
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

    public function edit(Voucher $voucher)
    {
        return view('admin.edit_voucher', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'voucher_name' => 'required|string|max:10',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $voucher->update($validated);

        return redirect()->route('vouchers.create')->with('success', 'Voucher updated successfully!');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('vouchers.create')->with('success', 'Voucher deleted successfully!');
    }
}
