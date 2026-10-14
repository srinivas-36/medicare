const CHECK_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
const ALERT_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 9v4" stroke-linecap="round"/><path d="M12 17h.01" stroke-linecap="round"/><path d="M10.3 4.8 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.8a2 2 0 0 0-3.4 0Z" stroke-linejoin="round"/></svg>';

export function registerConfirmDialog() {
    const root = document.createElement('div');
    root.className = 'confirm-dialog';
    root.hidden = true;
    root.innerHTML = `
        <button type="button" class="confirm-dialog__backdrop" data-confirm-dismiss tabindex="-1" aria-label="Close"></button>
        <div class="confirm-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message" tabindex="-1">
            <div class="confirm-dialog__icon" data-confirm-icon></div>
            <h2 id="confirm-dialog-title" class="confirm-dialog__title"></h2>
            <p id="confirm-dialog-message" class="confirm-dialog__message"></p>
            <p class="confirm-dialog__detail" data-confirm-detail hidden></p>
            <div class="confirm-dialog__actions">
                <button type="button" class="btn-secondary" data-confirm-dismiss>Go back</button>
                <button type="button" class="btn-primary" data-confirm-accept>Confirm</button>
            </div>
        </div>
    `;

    const title = root.querySelector('#confirm-dialog-title');
    const message = root.querySelector('#confirm-dialog-message');
    const detail = root.querySelector('[data-confirm-detail]');
    const icon = root.querySelector('[data-confirm-icon]');
    const accept = root.querySelector('[data-confirm-accept]');
    const panel = root.querySelector('.confirm-dialog__panel');

    let resolver = null;
    let lastFocus = null;

    const finish = (accepted) => {
        if (!resolver) {
            return;
        }

        const resolve = resolver;
        resolver = null;
        root.hidden = true;
        document.body.classList.remove('confirm-open');
        document.removeEventListener('keydown', onKeydown);

        if (lastFocus instanceof HTMLElement) {
            lastFocus.focus();
        }

        resolve(accepted);
    };

    const onKeydown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            finish(false);
        }
    };

    root.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest('[data-confirm-dismiss]')) {
            finish(false);
        }
    });

    accept.addEventListener('click', () => finish(true));

    window.appConfirm = (options = {}) => new Promise((resolve) => {
        if (resolver) {
            finish(false);
        }

        resolver = resolve;
        lastFocus = document.activeElement;
        const tone = options.tone === 'danger' ? 'danger' : 'primary';

        title.textContent = options.title || 'Please confirm';
        message.textContent = options.message || '';
        detail.textContent = options.detail || '';
        detail.hidden = !options.detail;
        icon.innerHTML = tone === 'danger' ? ALERT_ICON : CHECK_ICON;
        icon.dataset.tone = tone;
        accept.textContent = options.confirmLabel || 'Confirm';
        accept.className = tone === 'danger' ? 'btn-danger' : 'btn-primary';
        root.querySelectorAll('[data-confirm-dismiss].btn-secondary').forEach((button) => {
            button.textContent = options.cancelLabel || 'Go back';
        });

        root.hidden = false;
        document.body.classList.add('confirm-open');
        document.addEventListener('keydown', onKeydown);
        panel.focus();
        accept.focus();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const text = form.getAttribute('data-confirm');

        if (!text) {
            return;
        }

        if (form.dataset.confirming === 'ready') {
            delete form.dataset.confirming;

            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const submitter = event.submitter;

        window.appConfirm({
            title: form.dataset.confirmTitle || 'Please confirm',
            message: text,
            confirmLabel: form.dataset.confirmOk || 'Confirm',
            cancelLabel: form.dataset.confirmCancel || 'Go back',
            tone: form.dataset.confirmTone || 'primary',
        }).then((accepted) => {
            if (!accepted) {
                return;
            }

            if (!form.reportValidity()) {
                return;
            }

            form.dataset.confirming = 'ready';

            if (submitter instanceof HTMLElement) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });
    }, true);

    document.body.appendChild(root);
}
