( function () {
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const nameEnInput = document.getElementById('name_en');
        const locationInput = document.getElementById('location');
        const slugInput = document.getElementById('slug');

        if (!slugInput) return;

        const sourceInput = nameInput || nameEnInput || locationInput;

        if (!sourceInput) return;

        const slugify = (s) =>
            s.toString()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');
        
        let slugEdited = false;
        slugInput.addEventListener('input', () => { slugEdited = true; });

        const syncSlug = () => {
            if (!slugEdited || !slugInput.value) {
                const sourceValue = sourceInput.value.trim();

                if (sourceValue) {
                    slugInput.value = slugify(sourceValue);
                } else {
                    slugInput.value = '';
                }
            }
        };

        sourceInput.addEventListener('input', syncSlug);
        sourceInput.addEventListener('blur', syncSlug);

        if (!slugInput.value && sourceInput.value) syncSlug();
    });
})();