<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('brand-modal');
    if (!modal) return;

    const open = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-brand-modal]').forEach((btn) => {
        btn.addEventListener('click', open);
    });

    document.querySelectorAll('[data-close-brand-modal]').forEach((btn) => {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });

    document.querySelectorAll('[data-upload-trigger]').forEach((zone) => {
        const input = document.getElementById(zone.dataset.uploadTrigger);
        if (!input) return;

        zone.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            const name = input.files?.[0]?.name;
            const text = zone.querySelector('p');
            if (name && text) {
                text.textContent = name + ' selected';
            }
        });
    });

    @if ($showBrandModal ?? false)
    open();
    @endif

    if (new URLSearchParams(window.location.search).get('create_brand') === '1') {
        open();
    }
});
</script>
