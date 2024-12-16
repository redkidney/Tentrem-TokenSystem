<?php

namespace App\DataTables;

use App\Models\ChargingSession;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class ChargingSessionsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->setRowId('id')
            ->editColumn('id', function ($row) {
                return '<span class="text-center">' . $row->id . '</span>';
            })
            ->editColumn('room_no', function ($row) {
                return '<span class="text-center">' . $row->room_no . '</span>';
            })
            ->editColumn('phone', function ($row) {
                return '<span class="text-center">' . $row->phone . '</span>';
            })
            ->editColumn('charging_port', function ($row) {
                return '<span class="font-medium">Port ' . $row->charging_port . '</span>';
            })
            ->editColumn('voucher_name', function ($row) {
                return $row->voucher_name ?? '<span class="text-gray-400">N/A</span>';
            })
            ->editColumn('voucher_duration', function ($row) {
                return $row->voucher_duration 
                    ? '<span class="text-center">' . $row->voucher_duration . ' min</span>' 
                    : '<span class="text-gray-400">N/A</span>';
            })
            ->editColumn('voucher_price', function ($row) {
                return $row->voucher_price 
                    ? '<span class="text-right">Rp ' . number_format($row->voucher_price, 2, ',', '.') . '</span>'
                    : '<span class="text-gray-400">N/A</span>';
            })
            ->addColumn('used_duration', function ($row) {
                if ($row->used_time !== null) {
                    return '<span class="text-center">' . round($row->used_time / 60, 1) . ' min</span>';
                }
                return '<span class="text-gray-400">N/A</span>';
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at 
                    ? '<span class="text-center">' . $row->updated_at->format('Y-m-d H:i') . '</span>'
                    : '<span class="text-gray-400">N/A</span>';
            })
            ->addColumn('port_history', function ($row) {
                if (!$row->port_history) {
                    return '<span class="text-gray-400">No history</span>';
                }

                $history = collect($row->port_history)->map(function ($entry) {
                    $startTime = Carbon::parse($entry['start_time'])->format('H:i');
                    $endTime = $entry['end_time'] 
                        ? Carbon::parse($entry['end_time'])->format('H:i')
                        : '<span class="text-blue-500">ongoing</span>';
                    
                    $duration = $entry['end_time']
                        ? Carbon::parse($entry['start_time'])->diffInMinutes(Carbon::parse($entry['end_time']))
                        : null;
                    
                    $durationText = $duration !== null 
                        ? "<span class='text-gray-500'>({$duration} min)</span>"
                        : '';

                    return sprintf(
                        '<div class="mb-1">Port %d: <span class="font-medium">%s - %s</span> %s</div>',
                        $entry['port'],
                        $startTime,
                        $endTime,
                        $durationText
                    );
                })->join('');

                return '<div class="text-sm">' . $history . '</div>';
            })
            ->rawColumns(['id', 'room_no', 'phone', 'charging_port', 'voucher_name', 'voucher_duration', 
                         'voucher_price', 'used_duration', 'updated_at', 'port_history']);
    }

    public function query(ChargingSession $model): QueryBuilder
    {
        $query = $model->newQuery();

        if ($startDate = request()->get('start_date')) {
            $query->where('updated_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate = request()->get('end_date')) {
            $query->where('updated_at', '<=', $endDate . ' 23:59:59');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('charging-sessions-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('reports.charging-sessions'))
            ->orderBy(9, 'desc')
            ->selectStyleSingle()
            ->buttons([
                'excel',
                'csv',
                'pdf',
                'print',
                'reset',
                'reload',
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width(60)->className('text-center'),
            Column::make('guest_name')->title('Guest Name'),
            Column::make('room_no')->title('Room')->width(80)->className('text-center'),
            Column::make('phone')->title('Phone')->className('text-center'),
            Column::make('charging_port')->title('Port')->width(80),
            Column::make('voucher_name')->title('Voucher'),
            Column::make('voucher_duration')->title('Duration')->width(100),
            Column::make('used_duration')->title('Used')->width(100),
            Column::make('voucher_price')->title('Price')->width(120),
            Column::make('updated_at')->title('Date')->searchable(false),
            Column::computed('port_history')->title('Port History')
                ->searchable(false)
                ->orderable(false)
                ->width(200)
        ];
    }

    protected function filename(): string
    {
        return 'ChargingSessions_' . date('YmdHis');
    }
}