<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\ChargingSession;
use App\Models\Port;
use App\Models\Voucher;
use App\DataTables\ChargingSessionsDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index(ChargingSessionsDataTable $dataTable) {
        $vouchers = Voucher::all();

        return $dataTable->render('reports', [
            'vouchers' => $vouchers,
        ]);
    }

    public function getChargingSessions(Request $request)
    {
        // Build the query using the query builder
        $query = ChargingSession::with('voucher');

        // Apply filters for start_date and end_date
        if ($request->has('start_date') && $request->start_date) {
            $query->where('updated_at', '>=', $request->start_date . ' 00:00:00');
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('updated_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Return the DataTable
        return DataTables::eloquent($query)
            ->addColumn('voucher_name', function ($session) {
                return $session->voucher ? $session->voucher->voucher_name : 'N/A';
            })
            ->addColumn('duration', function ($session) {
                return $session->voucher ? $session->voucher->duration . ' min' : 'N/A';
            })
            ->addColumn('price', function ($session) {
                return $session->voucher ? 'Rp ' . number_format($session->voucher->price, 0, ',', '.') : 'N/A';
            })
            ->editColumn('updated_at', function ($session) {
                return $session->updated_at ? $session->updated_at->format('Y-m-d H:i') : 'N/A';
            })
            ->make(true);
    }

    public function exportCsv(Request $request)
    {
        // Apply date filters
        $query = ChargingSession::query()->with('voucher');

        if ($request->has('start_date') && $request->start_date) {
            $query->where('updated_at', '>=', $request->start_date . ' 00:00:00');
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('updated_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Fetch the filtered data
        $sessions = $query->get();

        // Prepare CSV data
        $csvData = [];
        $csvData[] = ['Session ID', 'Guest Name', 'Room Number', 'Phone Number', 'Port', 'Voucher Name', 'Duration', 'Price', 'Date'];

        foreach ($sessions as $session) {
            $csvData[] = [
                $session->id,
                $session->guest_name,
                $session->room_no,
                $session->phone,
                $session->charging_port,
                $session->voucher ? $session->voucher->voucher_name : 'N/A',
                $session->voucher ? $session->voucher->duration . ' min' : 'N/A',
                $session->voucher ? 'Rp ' . number_format($session->voucher->price, 2, ',', '.') : 'N/A',
                $session->updated_at ? $session->updated_at->format('Y-m-d H:i') : 'N/A',
            ];
        }

        // Output CSV
        $filename = 'ChargingSessions_' . now()->format('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
