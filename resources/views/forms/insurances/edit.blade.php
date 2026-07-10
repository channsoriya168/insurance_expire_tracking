@extends('forms.layout')

@section('title', 'Edit Policy')

@section('content')
    <h1>Edit a Policy</h1>

    @isset($status)
        <div class="status">{{ $status }}</div>
    @endisset

    @isset($error)
        <div class="error">{{ $error }}</div>
    @endisset

    <form method="POST" action="{{ url()->to(request()->getRequestUri()) }}">
        @csrf

        @if ($insurance === null)
            <p class="subtitle">Enter the policy number to look up.</p>

            <div class="field">
                <label for="policy_no">Policy No</label>
                <input type="text" name="policy_no" id="policy_no" value="{{ $policyNo ?? '' }}" autofocus>
            </div>

            <button type="submit" class="primary">Find Policy</button>
        @else
            <p class="subtitle">Editing <strong>{{ $insurance->policy_no }}</strong>. Fields marked * are required.</p>

            <input type="hidden" name="insurance_id" value="{{ $insurance->id }}">

            @php
                $values = $old ?: collect($fields)->mapWithKeys(fn ($field) => [$field => $insurance->{$field}])->all();
            @endphp

            @include('forms.insurances._fields', ['fields' => $fields, 'values' => $values, 'errors' => $errors ?? []])

            <button type="submit" class="primary">Save Changes</button>
        @endif
    </form>
@endsection
