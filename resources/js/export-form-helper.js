export function submitExportPost(url, payload) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrf) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrf;
        form.appendChild(csrfInput);
    }

    Object.entries(payload).forEach(([key, val]) => {
        if (Array.isArray(val)) {
            val.forEach((item) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `${key}[]`;
                input.value = item;
                form.appendChild(input);
            });
        } else if (val !== null && val !== undefined && val !== '') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }
    });

    document.body.appendChild(form);
    form.submit();
    setTimeout(() => form.remove(), 2000);
}
