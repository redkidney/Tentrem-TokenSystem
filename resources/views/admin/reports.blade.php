<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charging Sessions Report</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.5/dist/cdn.min.js" defer></script> --}}
    @push('styles')
    <style>
        #charging-sessions-table_wrapper {
            @apply bg-white rounded-lg shadow-sm;
        }

        #charging-sessions-table thead th {
            @apply bg-gray-50 text-gray-700 font-semibold px-4 py-3 border-b text-left;
        }

        #charging-sessions-table tbody td {
            @apply px-4 py-3 border-b border-gray-100;
        }

        /* Remove any default padding from DataTables */
        .dataTables_wrapper .dataTables_scroll div.dataTables_scrollBody>table>tbody>tr>td {
            padding-left: 1rem !important;  /* Use !important to override DataTables defaults */
        }

        .dataTables_wrapper .dataTables_scroll div.dataTables_scrollBody>table>thead>tr>th {
            padding-left: 1rem !important;
        }

        /* Ensure header cells don't get extra padding */
        table.dataTable thead th, table.dataTable thead td {
            padding-left: 1rem !important;
        }

        /* Ensure consistent row heights */
        #charging-sessions-table tbody tr {
            @apply h-12;
        }
    </style>
    @endpush
</head>

@extends('layouts.app')

<body class="bg-gray-100 p-6">
    <!-- Profile Icon with Dropdown -->
    <div class="relative" x-data="{ open: false }">
        <div class="absolute top-0 right-0">
            <button @click="open = !open" class="w-10 h-10 bg-gray-600 text-white rounded-full flex items-center justify-center">
                {{ Auth::user()->name[0] }}
            </button>
            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50">
                <form method="GET" action="{{ route('dashboard') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Dashboard</button>
                </form>
                <form method="GET" action="{{ route('registry') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Generate Token</button>
                </form>
                <form method="GET" action="{{ route('vouchers.create') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Create Voucher</button>
                </form>
                <form method="GET" action="{{ route('admin.monitor') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Charging Monitor</button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-red-100">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <div class="header">
        <img src="{{ asset('images/Asset-1.png') }}" alt="Logo" class="logo mx-auto" style="width: 200px; max-width: 100%; height: auto; margin-bottom: 1.5rem;">
    </div>

    <div class="max-w-6xl mx-auto mt-12 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-center text-2xl font-bold mb-6">Charging Sessions Report</h2>

        <!-- Date Filter -->
        <div class="flex flex-col md:flex-row md:items-center justify-end mb-4 space-y-4 md:space-y-0">
            <div class="flex items-center space-x-4">
                <div>
                    <label for="start_date" class="block text-gray-700">Start Date:</label>
                    <input type="date" id="start_date" class="p-2 border rounded-md" />
                </div>
                <div class="flex items-center space-x-2">
                    <div>
                        <label for="end_date" class="block text-gray-700">End Date:</label>
                        <input type="date" id="end_date" class="p-2 border rounded-md" />
                        <button id="filter" x-data @click="window.filterTable()" 
                        class="bg-blue-500 text-white py-2 px-4 rounded-md self-end disabled:bg-gray-300 hover:bg-blue-600 active:bg-blue-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Filter
                        </button>
                    </div>                   
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div>
            {{ $dataTable->table() }}
        </div>

        <!-- CSV Export Button -->
        <div class="mt-4 text-right">
            <form action="{{ route('reports.export.csv') }}" method="GET">
                <input type="hidden" id="csv_start_date" name="start_date">
                <input type="hidden" id="csv_end_date" name="end_date">
                <button type="submit" 
                    class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 active:bg-green-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Export to CSV
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const table = window.LaravelDataTables['charging-sessions-table'];
            
            window.filterTable = function () {
                table.draw();
            };

            // Date sync for CSV export
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");
            const csvStartDateInput = document.getElementById("csv_start_date");
            const csvEndDateInput = document.getElementById("csv_end_date");

            function syncFilterDates() {
                csvStartDateInput.value = startDateInput.value;
                csvEndDateInput.value = endDateInput.value;
            }

            startDateInput.addEventListener("change", syncFilterDates);
            endDateInput.addEventListener("change", syncFilterDates);
        });
    </script>
    @endpush

    @stack('scripts')
</body>
</html>
