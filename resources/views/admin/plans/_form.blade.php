@php
    $plan = $plan ?? null;
    $oldFeatures = old('features');
    if (is_array($oldFeatures)) {
        $featureItems = array_values(array_filter(array_map(function ($item) {
            if (! is_array($item)) {
                return null;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                return null;
            }

            return [
                'name' => $name,
                'enabled' => ! empty($item['enabled']),
            ];
        }, $oldFeatures)));
    } else {
        $featureItems = $plan?->editableFeatures() ?? [];
    }
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <x-input-label for="name" value="Plan Name" />
        <x-text-input id="name" name="name" :value="old('name', $plan?->name)" required />
        <x-input-error :messages="$errors->get('name')" />
    </div>
    <div class="col-md-6">
        <x-input-label for="slug" value="Slug" />
        <x-text-input id="slug" name="slug" :value="old('slug', $plan?->slug)" required placeholder="e.g. starter" />
        <x-input-error :messages="$errors->get('slug')" />
    </div>
    <div class="col-md-4">
        <x-input-label for="price_monthly" value="Monthly Price (₹)" />
        <x-text-input id="price_monthly" type="number" step="0.01" min="0" name="price_monthly" :value="old('price_monthly', $plan?->price_monthly ?? 0)" required />
        <x-input-error :messages="$errors->get('price_monthly')" />
    </div>
    <div class="col-md-4">
        <x-input-label for="price_yearly" value="Yearly Price (₹)" />
        <x-text-input id="price_yearly" type="number" step="0.01" min="0" name="price_yearly" :value="old('price_yearly', $plan?->price_yearly ?? 0)" required />
        <x-input-error :messages="$errors->get('price_yearly')" />
    </div>
    <div class="col-md-4">
        <x-input-label for="sort_order" value="Sort Order" />
        <x-text-input id="sort_order" type="number" min="0" name="sort_order" :value="old('sort_order', $plan?->sort_order ?? 0)" required />
        <x-input-error :messages="$errors->get('sort_order')" />
    </div>
</div>

<h3 class="h6 fw-semibold mb-3">Limits <span class="text-muted fw-normal">(leave blank for unlimited)</span></h3>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <x-input-label for="max_social_accounts" value="Max Social Accounts" />
        <x-text-input id="max_social_accounts" type="number" min="1" name="max_social_accounts" :value="old('max_social_accounts', $plan?->max_social_accounts)" />
        <x-input-error :messages="$errors->get('max_social_accounts')" />
    </div>
    <div class="col-md-6">
        <x-input-label for="max_posts_per_month" value="Max Posts / Month" />
        <x-text-input id="max_posts_per_month" type="number" min="1" name="max_posts_per_month" :value="old('max_posts_per_month', $plan?->max_posts_per_month)" />
        <x-input-error :messages="$errors->get('max_posts_per_month')" />
    </div>
</div>

<div class="mb-4">
    <x-input-label for="subtitle" value="Subtitle" />
    <x-text-input
        id="subtitle"
        name="subtitle"
        :value="old('subtitle', $plan?->subtitle)"
        placeholder="e.g. Perfect for growing teams and agencies"
        maxlength="255"
    />
    <x-input-error :messages="$errors->get('subtitle')" />
</div>

<h3 class="h6 fw-semibold mb-3">Features</h3>
<div class="border rounded-3 p-3 mb-3 bg-light" id="plan-features-box">
    <div class="row g-2 align-items-end mb-3">
        <div class="col-md-7">
            <label class="form-label small mb-1" for="feature-name-input">Feature name</label>
            <input type="text" id="feature-name-input" class="form-control" placeholder="e.g. Bulk Scheduling" maxlength="120">
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="feature-enabled-input" checked>
                <label class="form-check-label" for="feature-enabled-input">Enabled</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-success w-100" id="feature-add-btn">
                <i class="ti ti-plus"></i> Add
            </button>
        </div>
    </div>

    <div id="feature-list" class="d-flex flex-column gap-2">
        @forelse ($featureItems as $index => $feature)
            <div class="feature-row d-flex align-items-center gap-2 p-2 bg-white border rounded-2" data-feature-row>
                <input type="hidden" name="features[{{ $index }}][name]" value="{{ $feature['name'] }}">
                <input type="hidden" name="features[{{ $index }}][enabled]" value="{{ $feature['enabled'] ? '1' : '0' }}">
                <div class="form-check mb-0">
                    <input class="form-check-input feature-enabled-toggle" type="checkbox" {{ $feature['enabled'] ? 'checked' : '' }}>
                </div>
                <span class="flex-grow-1 fw-medium">{{ $feature['name'] }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger feature-delete-btn" title="Delete">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        @empty
            <p class="text-muted small mb-0" id="feature-empty">No features added yet.</p>
        @endforelse
    </div>
</div>

<div class="mb-4">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>

<div>
    <x-primary-button>{{ $plan ? 'Update Plan' : 'Create Plan' }}</x-primary-button>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
</div>

@push('scripts')
<script>
(() => {
    const list = document.getElementById('feature-list');
    const nameInput = document.getElementById('feature-name-input');
    const enabledInput = document.getElementById('feature-enabled-input');
    const addBtn = document.getElementById('feature-add-btn');
    if (!list || !nameInput || !addBtn) return;

    const ensureEmptyHint = () => {
        let empty = document.getElementById('feature-empty');
        const hasRows = list.querySelectorAll('[data-feature-row]').length > 0;
        if (!hasRows && !empty) {
            empty = document.createElement('p');
            empty.id = 'feature-empty';
            empty.className = 'text-muted small mb-0';
            empty.textContent = 'No features added yet.';
            list.appendChild(empty);
        }
        if (hasRows && empty) empty.remove();
    };

    const reindex = () => {
        list.querySelectorAll('[data-feature-row]').forEach((row, index) => {
            const nameField = row.querySelector('input[type="hidden"][name*="[name]"]');
            const enabledField = row.querySelector('input[type="hidden"][name*="[enabled]"]');
            if (nameField) nameField.name = `features[${index}][name]`;
            if (enabledField) enabledField.name = `features[${index}][enabled]`;
        });
        ensureEmptyHint();
    };

    const addFeature = () => {
        const name = (nameInput.value || '').trim();
        if (!name) {
            nameInput.focus();
            return;
        }

        const enabled = !!enabledInput?.checked;
        const index = list.querySelectorAll('[data-feature-row]').length;
        const row = document.createElement('div');
        row.className = 'feature-row d-flex align-items-center gap-2 p-2 bg-white border rounded-2';
        row.setAttribute('data-feature-row', '');
        row.innerHTML = `
            <input type="hidden" name="features[${index}][name]" value="">
            <input type="hidden" name="features[${index}][enabled]" value="${enabled ? '1' : '0'}">
            <div class="form-check mb-0">
                <input class="form-check-input feature-enabled-toggle" type="checkbox" ${enabled ? 'checked' : ''}>
            </div>
            <span class="flex-grow-1 fw-medium"></span>
            <button type="button" class="btn btn-sm btn-outline-danger feature-delete-btn" title="Delete">
                <i class="ti ti-trash"></i>
            </button>
        `;
        row.querySelector('input[name*="[name]"]').value = name;
        row.querySelector('span').textContent = name;

        document.getElementById('feature-empty')?.remove();
        list.appendChild(row);
        nameInput.value = '';
        if (enabledInput) enabledInput.checked = true;
        nameInput.focus();
        reindex();
    };

    addBtn.addEventListener('click', addFeature);
    nameInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addFeature();
        }
    });

    list.addEventListener('click', (e) => {
        const btn = e.target.closest('.feature-delete-btn');
        if (!btn) return;
        btn.closest('[data-feature-row]')?.remove();
        reindex();
    });

    list.addEventListener('change', (e) => {
        const toggle = e.target.closest('.feature-enabled-toggle');
        if (!toggle) return;
        const row = toggle.closest('[data-feature-row]');
        const enabledField = row?.querySelector('input[type="hidden"][name*="[enabled]"]');
        if (enabledField) enabledField.value = toggle.checked ? '1' : '0';
    });
})();
</script>
@endpush
