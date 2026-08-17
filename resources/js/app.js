import './bootstrap';

/**
 * Instant search: any <form data-instant-search> auto-submits a short
 * moment after the user stops typing in its text inputs (debounced), and
 * immediately when a select/filter inside it changes.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-instant-search]').forEach((form) => {
        let timer;

        form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => form.submit(), 400);
            });
        });

        form.querySelectorAll('select, input[type="date"]').forEach((field) => {
            field.addEventListener('change', () => form.submit());
        });
    });
});
