@extends('layouts.student')

@section('content')

<style>
    .page-header {
        background-image: linear-gradient(rgba(33,28,54,0.75), rgba(33,28,54,0.75)), url("{{ asset('images/studio-control-room.jpg') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff; padding: 36px 40px;
    }
    .page-header h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .page-header p { color: rgba(255,255,255,0.6); font-size: 14px; }

    .page-body { max-width: 700px; margin: 0 auto; padding: 32px 40px; }

    .item-row { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; }
    .item-row select { flex: 2; }
    .item-row input { flex: 1; }
    .btn-remove-row {
        background: #FEE2E2; color: #991B1B; border: none;
        border-radius: 8px; padding: 8px 12px; font-size: 13px; cursor: pointer;
    }
    .btn-add-row {
        background: #F3E8FF; color: #5B21B6; border: none;
        border-radius: 8px; padding: 8px 16px; font-size: 13px;
        cursor: pointer; margin-bottom: 16px;
    }
    .availability-hint { font-size: 12px; margin-top: 4px; }
    .availability-hint.ok { color: #065F46; }
    .availability-hint.warn { color: #991B1B; }
    .date-row { display: flex; gap: 10px; }
    .date-row > div { flex: 1; }
</style>

<div class="page-header">
    <h2>📋 New Borrowing Request</h2>
    <p>Request equipment or attire — subject to staff approval</p>
</div>

<div class="page-body">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('borrowings.index') }}" class="btn btn-sm btn-outline-secondary">← Back to My Borrowings</a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('borrowings.store') }}">
                @csrf

                <div class="mb-3 date-row">
                    <div>
                        <label class="form-label" style="font-size:13px;">Pickup Date <span class="text-danger">*</span></label>
                        <input type="date" name="pickup_date" id="pickup_date" class="form-control" style="font-size:13px;"
                               min="{{ date('Y-m-d') }}" value="{{ old('pickup_date') }}" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:13px;">Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" id="return_date" class="form-control" style="font-size:13px;"
                               min="{{ date('Y-m-d') }}" value="{{ old('return_date') }}" required>
                    </div>
                </div>

                <label class="form-label" style="font-size:13px;">Items <span class="text-danger">*</span></label>
                <div id="item-rows">
                    <div class="item-row-wrapper">
                        <div class="item-row">
                            <select name="items[0][item_id]" class="form-control item-select" style="font-size:13px;" required>
                                <option value="">-- Select item --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->item_id }}" data-available="{{ $item->available_quantity }}"
                                        {{ (old('items.0.item_id', $selectedItem)) == $item->item_id ? 'selected' : '' }}>
                                        {{ $item->item_name }} ({{ $item->available_quantity }} in stock)
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="items[0][quantity]" class="form-control item-quantity" style="font-size:13px;"
                                   min="1" value="{{ old('items.0.quantity', 1) }}" placeholder="Qty" required>
                        </div>
                        <div class="availability-hint"></div>
                    </div>
                </div>

                <button type="button" class="btn-add-row" id="add-row">+ Add another item</button>

                <div class="mb-4">
                    <label class="form-label" style="font-size:13px;">Purpose / Event</label>
                    <textarea name="purpose" class="form-control" style="font-size:13px;" rows="3"
                              placeholder="e.g. University Performance">{{ old('purpose') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                        ✅ Submit Request
                    </button>
                    <a href="{{ route('borrowings.index') }}" class="btn btn-outline-secondary" style="font-size:13px;border-radius:8px;">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<template id="item-row-template">
    <div class="item-row-wrapper">
        <div class="item-row">
            <select name="items[__INDEX__][item_id]" class="form-control item-select" style="font-size:13px;" required>
                <option value="">-- Select item --</option>
                @foreach($items as $item)
                    <option value="{{ $item->item_id }}" data-available="{{ $item->available_quantity }}">{{ $item->item_name }} ({{ $item->available_quantity }} in stock)</option>
                @endforeach
            </select>
            <input type="number" name="items[__INDEX__][quantity]" class="form-control item-quantity" style="font-size:13px;" min="1" value="1" placeholder="Qty" required>
            <button type="button" class="btn-remove-row">✕</button>
        </div>
        <div class="availability-hint"></div>
    </div>
</template>

<script>
    let rowIndex = 1;
    document.getElementById('add-row').addEventListener('click', function () {
        const template = document.getElementById('item-row-template').innerHTML.replaceAll('__INDEX__', rowIndex);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template;
        const rowEl = wrapper.firstElementChild;
        document.getElementById('item-rows').appendChild(rowEl);
        rowIndex++;
    });

    document.getElementById('item-rows').addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove-row')) {
            e.target.closest('.item-row-wrapper').remove();
        }
    });

    // AJAX availability check for each item row whenever item/dates/quantity change
    const availabilityUrl = "{{ route('borrowings.availability') }}";
    let debounceTimer = null;

    function checkAvailability(wrapper) {
        const select = wrapper.querySelector('.item-select');
        const qtyInput = wrapper.querySelector('.item-quantity');
        const hint = wrapper.querySelector('.availability-hint');
        const pickup = document.getElementById('pickup_date').value;
        const ret = document.getElementById('return_date').value;

        if (!select.value || !pickup || !ret) {
            hint.textContent = '';
            hint.className = 'availability-hint';
            return;
        }

        const params = new URLSearchParams({
            item_id: select.value,
            pickup_date: pickup,
            return_date: ret,
        });

        fetch(availabilityUrl + '?' + params.toString())
            .then(r => r.json())
            .then(data => {
                const available = data.available;
                const requested = parseInt(qtyInput.value || '0', 10);

                if (requested > available) {
                    hint.textContent = `Only ${available} unit(s) available during the selected reservation period.`;
                    hint.className = 'availability-hint warn';
                } else {
                    hint.textContent = `Available: ${available} unit(s) during the selected reservation period.`;
                    hint.className = 'availability-hint ok';
                }
            })
            .catch(() => {
                hint.textContent = '';
                hint.className = 'availability-hint';
            });
    }

    function checkAllAvailability() {
        document.querySelectorAll('#item-rows .item-row-wrapper').forEach(checkAvailability);
    }

    function debouncedCheckAll() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkAllAvailability, 300);
    }

    document.getElementById('pickup_date').addEventListener('change', debouncedCheckAll);
    document.getElementById('return_date').addEventListener('change', debouncedCheckAll);

    document.getElementById('item-rows').addEventListener('input', function (e) {
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-select')) {
            checkAvailability(e.target.closest('.item-row-wrapper'));
        }
    });
    document.getElementById('item-rows').addEventListener('change', function (e) {
        if (e.target.classList.contains('item-select')) {
            checkAvailability(e.target.closest('.item-row-wrapper'));
        }
    });

    // Run once on load if dates/items are pre-filled (e.g. validation error redisplay)
    debouncedCheckAll();
</script>

@endsection
