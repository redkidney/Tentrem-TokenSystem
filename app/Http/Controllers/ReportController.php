<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\Voucher;
use App\DataTables\ChargingSessionsDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index(ChargingSessionsDataTable $dataTable) {
        $vouchers = Voucher::all();
        return $dataTable->render('admin.reports', ['vouchers' => $vouchers]);
    }

    public function exportCsv(Request $request)
    {
        $query = ChargingSession::query();

        if ($request->has('start_date') && $request->start_date) {
            $query->where('updated_at', '>=', $request->start_date . ' 00:00:00');
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('updated_at', '<=', $request->end_date . ' 23:59:59');
        }

        $sessions = $query->get();

        $csvData = [];
        $csvData[] = ['Session ID', 'Guest Name', 'Room Number', 'Phone Number', 'Voucher Name', 'Duration', 'Used Duration', 'Price', 'Date', 'Port History'];

        foreach ($sessions as $session) {
            try {
                $usedDuration = 'N/A';
                if ($session->used_time !== null) {
                    $usedDuration = round($session->used_time / 60, 1) . ' min';
                }

                $portHistory = 'Port ' . $session->charging_port;
                if ($session->port_history) {
                    $portHistory = collect($session->port_history)->map(function ($entry) {
                        $start = \Carbon\Carbon::parse($entry['start_time'])->format('H:i');
                        $end = isset($entry['end_time']) ? \Carbon\Carbon::parse($entry['end_time'])->format('H:i') : 'ongoing';
                        return "P{$entry['port']}: {$start}-{$end}";
                    })->implode(', ');
                }

                $csvData[] = [
                    $session->id,
                    $session->guest_name,
                    $session->room_no,
                    $session->phone,
                    $session->voucher_name,
                    $session->voucher_duration . ' min',
                    $usedDuration,
                    'Rp ' . number_format($session->voucher_price, 2, ',', '.'),
                    $session->updated_at ? $session->updated_at->format('Y-m-d H:i') : 'N/A',
                    $portHistory
                ];
            } catch (\Exception $e) {
                Log::error('CSV Export Error', ['session_id' => $session->id, 'error' => $e->getMessage()]);
                $csvData[] = [$session->id, 'Error Processing Session', '', '', '', '', '', '', '', ''];
            }
        }

        $filename = 'ChargingSessions_' . now()->format('YmdHis') . '.csv';
        return response()->streamDownload(
            function () use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }
}