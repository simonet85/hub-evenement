if (document.querySelector('[data-scan]')) {
    import('./scanner.js');
}

if (document.querySelector('[data-map-picker]')) {
    import('./map-picker.js');
}

if (document.querySelector('[data-reveal]')) {
    import('./reveal.js');
}

const programMap = document.querySelector('[data-program-map]');

if (programMap) {
    const loadProgramMap = () => import('./program-map.js');

    if ('IntersectionObserver' in window) {
        const mapObserver = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    mapObserver.disconnect();
                    loadProgramMap();
                }
            },
            { rootMargin: '200px 0px' },
        );

        mapObserver.observe(programMap);
    } else {
        loadProgramMap();
    }
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
