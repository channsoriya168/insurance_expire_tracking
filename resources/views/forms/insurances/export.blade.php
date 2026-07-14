@extends('forms.layout')

@section('title', 'Export Policies')

@section('content')
    <h1>Export Policies</h1>
    <p class="subtitle">Choose a filter, then get an Excel file sent to this chat.</p>

    @isset($error)
        <div class="error">{{ $error }}</div>
    @endisset

    @isset($sent)
        <div class="status">Sent! Check this Telegram chat for the Excel file.</div>
    @endisset

    <form method="POST" action="{{ url()->to(request()->getRequestUri()) }}" id="export-form">
        @csrf
        <input type="hidden" name="filter" id="filter-value">

        <div class="field">
            <label for="filter-type">Filter</label>
            <select id="filter-type">
                <option value="all">All policies</option>
                <option value="month">A month</option>
                <option value="day">A single day</option>
                <option value="range">A date range</option>
            </select>
        </div>

        <div class="field" data-filter-field="month">
            <label for="filter-month">Month</label>
            <input type="month" id="filter-month">
        </div>

        <div class="field" data-filter-field="day">
            <label for="filter-day">Day</label>
            <input type="date" id="filter-day">
        </div>

        <div class="field" data-filter-field="range">
            <label for="filter-from">From</label>
            <input type="date" id="filter-from">
        </div>

        <div class="field" data-filter-field="range">
            <label for="filter-to">To</label>
            <input type="date" id="filter-to">
        </div>

        <button type="submit" class="primary">Send to Telegram</button>
    </form>

    <script>
        (function () {
            const initial = @json($filter);
            const type = document.getElementById('filter-type');
            const fields = document.querySelectorAll('[data-filter-field]');
            const monthInput = document.getElementById('filter-month');
            const dayInput = document.getElementById('filter-day');
            const fromInput = document.getElementById('filter-from');
            const toInput = document.getElementById('filter-to');
            const filterValue = document.getElementById('filter-value');
            const form = document.getElementById('export-form');

            function detectType(value) {
                if (!value || value === 'all') return 'all';
                if (/^\d{4}-\d{2}$/.test(value)) return 'month';
                if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return 'day';
                if (/^\d{4}-\d{2}-\d{2}\.\.\d{4}-\d{2}-\d{2}$/.test(value)) return 'range';
                return 'all';
            }

            function applyInitial(value) {
                type.value = detectType(value);

                if (type.value === 'month') {
                    monthInput.value = value;
                } else if (type.value === 'day') {
                    dayInput.value = value;
                } else if (type.value === 'range') {
                    const [from, to] = value.split('..');
                    fromInput.value = from;
                    toInput.value = to;
                }
            }

            function toggleFields() {
                fields.forEach((field) => {
                    field.hidden = field.dataset.filterField !== type.value;
                });
            }

            type.addEventListener('change', toggleFields);

            form.addEventListener('submit', function (event) {
                let value = 'all';

                if (type.value === 'month') {
                    value = monthInput.value;
                } else if (type.value === 'day') {
                    value = dayInput.value;
                } else if (type.value === 'range') {
                    value = `${fromInput.value}..${toInput.value}`;
                }

                if (type.value !== 'all' && (!value || value === '..')) {
                    event.preventDefault();
                    return;
                }

                filterValue.value = value;
            });

            applyInitial(initial);
            toggleFields();
        })();
    </script>
@endsection
