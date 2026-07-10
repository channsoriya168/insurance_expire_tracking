@extends('forms.layout')

@section('title', 'Add Policy')

@section('content')
    <h1>Add a New Policy</h1>
    <p class="subtitle">Fields marked * are required. Leave others blank to skip.</p>

    @isset($status)
        <div class="status">{{ $status }}</div>
    @endisset

    @php($values = $old ?: [])

    <form method="POST" action="{{ url()->to(request()->getRequestUri()) }}">
        @csrf

        @include('forms.insurances._fields', ['fields' => $fields, 'values' => $values, 'errors' => $errors])

        <button type="submit" class="primary">Save Policy</button>
    </form>
@endsection
