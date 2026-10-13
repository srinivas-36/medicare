import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/*
    After a real submit (browser validation already passed), disable the button
    that was clicked so the same form is not sent twice. A fresh page load
    clears the disabled state if the server returns an error.
*/
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.dataset.noLock === 'true') {
        return;
    }

    const button = event.submitter;

    if (!(button instanceof HTMLButtonElement)) {
        return;
    }

    button.dataset.loading = 'true';
    button.setAttribute('aria-busy', 'true');

    window.setTimeout(() => {
        button.disabled = true;
    }, 0);
});
