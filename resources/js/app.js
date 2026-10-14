import './bootstrap';
import { registerConfirmDialog } from './confirm-dialog';

import Alpine from 'alpinejs';
import { registerAnalysisPoll } from './analysis-poll';
import { registerDoctorReviews } from './doctor-reviews';
import { registerReferralPreview } from './referral-preview';
import { registerVisitChat } from './visit-chat';

window.Alpine = Alpine;

registerAnalysisPoll(Alpine);
registerDoctorReviews(Alpine);
registerReferralPreview(Alpine);
registerVisitChat(Alpine);

Alpine.start();

registerConfirmDialog();

/*
    After a real submit (browser validation already passed), disable the button
    that was clicked so the same form is not sent twice. A fresh page load
    clears the disabled state if the server returns an error.
*/
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.dataset.noLock === 'true' || event.defaultPrevented) {
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
