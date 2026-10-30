@extends('admin.layout')

@section('title', 'Fee rules')
@section('subtitle', 'City and spot parking rates shown in the mobile app')

@section('content')
<div class="ol-card card" style="margin-bottom:20px;">
    <h3>Add fee rule</h3>
    <form method="POST" action="{{ route('admin.fee-rules.store') }}">
        @csrf
        <div class="ol-toolbar toolbar" style="align-items:end;">
            <div class="ol-field field" style="flex:1;min-width:120px;margin:0;">
                <label for="city">City</label>
                <input id="city" name="city" value="{{ old('city', 'Harare') }}" required>
            </div>
            <div class="ol-field field" style="flex:1;min-width:120px;margin:0;">
                <label for="kind">Kind</label>
                <select id="kind" name="kind" required>
                    <option value="ON_STREET">ON_STREET</option>
                    <option value="PARKADE">PARKADE</option>
                    <option value="LOT">LOT</option>
                </select>
            </div>
            <div class="ol-field field" style="width:110px;margin:0;">
                <label for="amount">Amount</label>
                <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', '1.00') }}" required>
            </div>
            <div class="ol-field field" style="width:90px;margin:0;">
                <label for="currency">Currency</label>
                <input id="currency" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required>
            </div>
            <input type="hidden" name="unit" value="HOUR">
            <div class="ol-field field" style="flex:1.4;min-width:160px;margin:0;">
                <label for="source_note">Note</label>
                <input id="source_note" name="source_note" value="{{ old('source_note') }}">
            </div>
            <button type="submit" class="primary">Add</button>
        </div>
    </form>
</div>

<div class="ol-card card">
    <div class="ol-table-wrap">
        <table>
            <thead>
            <tr>
                <th>City</th>
                <th>Kind</th>
                <th>Rate</th>
                <th>Spot</th>
                <th>Note</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rules as $rule)
                <tr>
                    <td colspan="6" style="padding:0;border:0;">
                        <form method="POST" action="{{ route('admin.fee-rules.update', $rule) }}" class="ol-toolbar toolbar" style="padding:10px 0;border-bottom:1px solid var(--line);align-items:center;">
                            @csrf
                            @method('PUT')
                            <input name="city" value="{{ $rule->city }}" style="max-width:140px;" required>
                            <select name="kind" required>
                                @foreach (['ON_STREET','PARKADE','LOT'] as $kind)
                                    <option value="{{ $kind }}" @selected($rule->kind === $kind)>{{ $kind }}</option>
                                @endforeach
                            </select>
                            <input name="amount" type="number" step="0.01" min="0" value="{{ $rule->amount }}" style="max-width:100px;" required>
                            <input name="currency" value="{{ $rule->currency }}" maxlength="3" style="max-width:80px;" required>
                            <input type="hidden" name="unit" value="HOUR">
                            <input name="parking_spot_id" value="{{ $rule->parking_spot_id }}" placeholder="Spot id (optional)" style="max-width:180px;">
                            <input name="source_note" value="{{ $rule->source_note }}" placeholder="Note" style="flex:1;">
                            <button type="submit" class="secondary">Save</button>
                        </form>
                        <form method="POST" action="{{ route('admin.fee-rules.destroy', $rule) }}" style="margin:-6px 0 10px;" data-ol-confirm="Delete this fee rule?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="ol-muted muted">No fee rules yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
