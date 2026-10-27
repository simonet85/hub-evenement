import './bootstrap';

if (document.querySelector('[data-scan]')) {
    import('./scanner.js');
}

const rsvpForm = document.querySelector('[data-rsvp-form]');

if (rsvpForm) {
    const attendanceFields = rsvpForm.querySelector('[data-attendance-fields]');

    const syncAttendanceFields = () => {
        const selected = rsvpForm.querySelector('input[name="rsvp_status"]:checked');

        if (!selected || !attendanceFields) {
            return;
        }

        attendanceFields.hidden = selected.value !== 'attending';
    };

    rsvpForm.querySelectorAll('input[name="rsvp_status"]').forEach((radio) => {
        radio.addEventListener('change', syncAttendanceFields);
    });

    syncAttendanceFields();
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-target]');

    if (!button) {
        return;
    }

    const input = document.getElementById(button.dataset.copyTarget);

    if (!input) {
        return;
    }

    try {
        await navigator.clipboard.writeText(input.value);

        const label = button.textContent;
        button.textContent = 'Copié';
        window.setTimeout(() => {
            button.textContent = label;
        }, 2000);
    } catch {
        input.select();
    }
});
