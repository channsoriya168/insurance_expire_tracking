@php
    use App\Telegram\Conversations\PolicyFieldSteps;
@endphp

@foreach ($fields as $field)
    @php
        $step = PolicyFieldSteps::get($field);
        $raw = $values[$field] ?? null;
        $value = $raw instanceof \Carbon\CarbonInterface ? $raw->format('Y-m-d') : (string) ($raw ?? '');
    @endphp
    <div class="field">
        <label for="{{ $field }}">
            {{ PolicyFieldSteps::label($field) }}@if (! $step->skippable) *@endif
        </label>

        @if ($field === 'contact_method')
            <select name="{{ $field }}" id="{{ $field }}">
                <option value="">-- select --</option>
                @foreach (PolicyFieldSteps::contactMethods() as $method)
                    <option value="{{ $method }}" @selected($value === $method)>{{ $method }}</option>
                @endforeach
            </select>
        @elseif ($field === 'remarks')
            <textarea name="{{ $field }}" id="{{ $field }}">{{ $value }}</textarea>
        @elseif (PolicyFieldSteps::isDateField($field))
            <input type="date" name="{{ $field }}" id="{{ $field }}" value="{{ $value }}">
        @elseif (PolicyFieldSteps::isNumericField($field))
            <input type="number" step="0.01" name="{{ $field }}" id="{{ $field }}" value="{{ $value }}">
        @else
            <input type="text" name="{{ $field }}" id="{{ $field }}" value="{{ $value }}">
        @endif

        <div class="hint">{{ $step->prompt }}</div>

        @if (isset($errors[$field]))
            <div class="field-error">{{ $errors[$field] }}</div>
        @endif
    </div>
@endforeach
