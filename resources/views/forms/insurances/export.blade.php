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

    <form method="POST" action="{{ url()->to(request()->getRequestUri()) }}">
        @csrf

        <div class="field">
            <label for="filter">Filter</label>
            <input type="text" name="filter" id="filter" value="{{ $filter }}">
            <div class="hint">
                <code>all</code> for everything, <code>YYYY-MM</code> for a month, or
                <code>YYYY-MM-DD..YYYY-MM-DD</code> for a custom range.
            </div>
        </div>

        <button type="submit" class="primary">Send to Telegram</button>
    </form>
@endsection
