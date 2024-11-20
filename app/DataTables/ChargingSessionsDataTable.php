<?php

namespace App\DataTables;

use App\Models\ChargingSession;
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
            ->setRowId('id') // Set row ID for better table rendering
            ->addColumn('voucher_name', function ($row) {
                return $row->voucher ? $row->voucher->voucher_name : 'N/A';
            })
            ->addColumn('duration', function ($row) {
                return $row->voucher ? $row->voucher->duration . ' min' : 'N/A';
            })
            ->addColumn('price', function ($row) {
                return $row->voucher ? 'Rp ' . number_format($row->voucher->price, 2, ',', '.') : 'N/A';
            })
            ->editColumn('updated_at', function ($row) {
                // Format the date
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

        \Log::info('Generated Query:', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('charging-sessions-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('reports.charging-sessions'))
            ->orderBy(6, 'desc')
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