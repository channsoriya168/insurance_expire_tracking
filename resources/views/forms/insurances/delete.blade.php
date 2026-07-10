@extends('forms.layout')

@section('title', 'Delete Policy')

@section('content')
    <h1>Delete a Policy</h1>

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
            <p class="subtitle">Confirm you want to permanently delete this policy.</p>

            <dl class="summary">
                <dt>Policy No</dt>
                <dd>{{ $insurance->policy_no }}</dd>
                <dt>Insured Name</dt>
                <dd>{{ $insurance->insured_name }}</dd>
                <dt>Expiry Date</dt>
                <dd>{{ $insurance->expiry_date->format('Y-m-d') }}</dd>
            </dl>

            <input type="hidden" name="insurance_id" value="{{ $insurance->id }}">

            <button type="submit" class="danger">Confirm Delete</button>
        @endif
    </form>
@endsection
