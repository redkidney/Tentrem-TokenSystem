<?php

namespace App\DataTables;

use App\Models\ChargingSession;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ChargingSessionsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->setRowId('id')
            ->addColumn('voucher_name', function ($row) {
                // Safely handle cases where voucher might be an ID or null
                if ($row->voucher) {
                    // If it's an ID, use the relationship to fetch the voucher
                    $voucher = is_numeric($row->voucher) 
                        ? Voucher::find($row->voucher) 
                        : $row->voucher;
                    
                    return $voucher ? $voucher->voucher_name : 'N/A';
                }
                return 'N/A';
            })
            ->addColumn('duration', function ($row) {
                if ($row->voucher) {
                    $voucher = is_numeric($row->voucher) 
                        ? Voucher::find($row->voucher) 
                        : $row->voucher;
                    
                    return $voucher ? $voucher->duration . ' min' : 'N/A';
                }
                return 'N/A';
            })
            ->addColumn('price', function ($row) {
                if ($row->voucher) {
                    $voucher = is_numeric($row->voucher) 
                        ? Voucher::find($row->voucher) 
                        : $row->voucher;
                    
                    return $voucher 
                        ? 'Rp ' . number_format($voucher->price, 2, ',', '.') 
                        : 'N/A';
                }
                return 'N/A';
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at ? $row->updated_at->format('Y-m-d H:i') : 'N/A';
            });
    }

    public function query(ChargingSession $model): QueryBuilder
    {
        $query = $model->newQuery()->with('voucher');

        // Apply date filtering if provided
        if ($startDate = request()->get('start_date')) {
            $query->where('updated_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate = request()->get('end_date')) {
            $query->where('updated_at', '<=', $endDate . ' 23:59:59');
        }

        // Log the query
        \Log::info('Generated Query:', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('charging-sessions-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('reports.charging-sessions'))
            ->orderBy(8, 'desc')
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

    public function getColumns(): array
    {
        return [
            Column::make('id')->title('Session ID'),
            Column::make('guest_name')->title('Guest Name'),
            Column::make('room_no')->title('Room Number'),
            Column::make('phone')->title('Phone Number'),
            Column::make('charging_port')->title('Port'),
            Column::make('voucher_name')->title('Voucher Name')->orderable(false)->searchable(false),
            Column::make('duration')->title('Duration')->orderable(false)->searchable(false),
            Column::make('price')->title('Price')->orderable(false)->searchable(false),
            Column::make('updated_at')->title('Date')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'ChargingSessions_' . date('YmdHis');
    }
}