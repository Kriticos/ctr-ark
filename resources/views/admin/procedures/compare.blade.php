@extends('layouts.admin')

@section('title', 'Comparar Versões')

@section('content')
<div class="p-6 space-y-4">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Comparação de versões: v{{ $from->version_number }} x v{{ $to->version_number }}
    </h1>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                    <th class="p-2 text-left">Versão {{ $from->version_number }}</th>
                    <th class="p-2 text-left">Versão {{ $to->version_number }}</th>
                </tr>
            </thead>
            <tbody class="font-mono">
                @foreach($diffRows as $row)
                    @php
                        $rowClass = match($row['status']) {
                            'added' => 'bg-green-50 dark:bg-green-900/20',
                            'removed' => 'bg-red-50 dark:bg-red-900/20',
                            'changed' => 'bg-amber-50 dark:bg-amber-900/20',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="p-2 align-top border-t border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-200">{{ $row['left'] }}</td>
                        <td class="p-2 align-top border-t border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-200">{{ $row['right'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

