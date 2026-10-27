import { Html5Qrcode } from 'html5-qrcode';

const root = document.querySelector('[data-scan]');

if (root) {
    init(root);
}

function init(root) {
    const recordUrl = root.dataset.recordUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const result = root.querySelector('[data-result]');
    const resultMessage = root.querySelector('[data-result-message]');
    const guestName = root.querySelector('[data-guest-name]');
    const guestStatus = root.querySelector('[data-guest-status]');
    const guestSeats = root.querySelector('[data-guest-seats]');
    const guestNames = root.querySelector('[data-guest-names]');
    const guestCheckIn = root.querySelector('[data-guest-check-in]');

    const confirmButton = root.querySelector('[data-confirm]');
    const undoButton = root.querySelector('[data-undo]');
    const nextButton = root.querySelector('[data-next]');

    const manualForm = root.querySelector('[data-manual-form]');
    const codeInput = root.querySelector('[data-code-input]');

    const startButton = root.querySelector('[data-camera-start]');
    const stopButton = root.querySelector('[data-camera-stop]');
    const cameraStatus = root.querySelector('[data-camera-status]');
    const reader = root.querySelector('[data-qr-reader]');

    const counterHouseholds = root.querySelector('[data-counter-households]');
    const counterSeats = root.querySelector('[data-counter-seats]');

    const scanner = new Html5Qrcode(reader.id, { verbose: false });

    let lastCode = null;
    let currentGuest = null;
    let pending = false;
    let paused = false;

    manualForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const code = codeInput.value.trim();

        if (code === '') {
            return;
        }

        codeInput.value = '';
        verify(code);
    });

    startButton.addEventListener('click', startCamera);
    stopButton.addEventListener('click', stopCamera);
    nextButton.addEventListener('click', reset);
    confirmButton.addEventListener('click', () => verify(lastCode, true));
    undoButton.addEventListener('click', undoLastCheckIn);

    async function startCamera() {
        if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
            setCameraStatus('La caméra nécessite HTTPS ou localhost. Utilisez la saisie manuelle.', true);

            return;
        }

        setCameraStatus('Autorisation de la caméra en cours…');

        try {
            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: (width, height) => {
                        const size = Math.max(160, Math.floor(Math.min(width, height) * 0.7));

                        return { width: size, height: size };
                    },
                },
                (decodedText) => {
                    if (paused || pending) {
                        return;
                    }

                    paused = true;
                    scanner.pause(true);
                    verify(decodedText);
                },
                () => {},
            );

            paused = false;
            startButton.disabled = true;
            stopButton.hidden = false;
            setCameraStatus('Caméra active. Présentez le QR dans le cadre.');
        } catch (error) {
            setCameraStatus(
                `Caméra indisponible (${error}). Saisissez le code ou l’URL ci-dessous.`,
                true,
            );
        }
    }

    async function stopCamera() {
        try {
            if (scanner.isScanning) {
                await scanner.stop();
            }
        } catch {
            // Camera already stopped.
        }

        paused = false;
        startButton.disabled = false;
        stopButton.hidden = true;
        setCameraStatus('Caméra arrêtée. La saisie manuelle reste disponible.');
    }

    async function verify(code, confirmed = false) {
        if (pending || !code) {
            return;
        }

        pending = true;
        setActionsDisabled(true);
        pauseCamera();

        try {
            const data = await request(recordUrl, 'POST', { code, confirm: confirmed });

            lastCode = code;
            render(data);
        } catch (error) {
            renderInvalid(error);
        } finally {
            pending = false;
            setActionsDisabled(false);
        }
    }

    async function undoLastCheckIn() {
        if (pending || !currentGuest?.undo_url) {
            return;
        }

        pending = true;
        setActionsDisabled(true);

        try {
            const data = await request(currentGuest.undo_url, 'DELETE');

            render(data);
        } catch (error) {
            renderInvalid(error);
        } finally {
            pending = false;
            setActionsDisabled(false);
        }
    }

    function reset() {
        result.hidden = true;
        currentGuest = null;
        lastCode = null;

        if (scanner.isScanning) {
            paused = false;
            scanner.resume();
            setCameraStatus('Caméra active. Présentez le QR dans le cadre.');
        } else {
            codeInput.focus();
        }
    }

    function render(data) {
        currentGuest = data.guest;

        const guest = data.guest;
        const requiresConfirmation = data.status === 'requires_confirmation';
        const checkedIn = data.status === 'checked_in' || data.status === 'already_checked_in';

        resultMessage.textContent = data.message;
        resultMessage.className = requiresConfirmation
            ? 'text-caption text-warning'
            : data.status === 'undone'
              ? 'text-caption text-ink-muted'
              : 'text-caption text-success';

        guestName.textContent = guest.name;
        guestStatus.textContent = guest.rsvp_label;
        guestStatus.className = `status-pill ${statusClass(guest.rsvp_status)}`;

        guestSeats.textContent = guest.rsvp_status === 'attending'
            ? `${guest.seats} personne${guest.seats > 1 ? 's' : ''} confirmée${guest.seats > 1 ? 's' : ''}`
            : `${guest.allowed_seats} place${guest.allowed_seats > 1 ? 's' : ''} autorisée${guest.allowed_seats > 1 ? 's' : ''}`;

        guestNames.textContent = guest.attendee_names || 'Non renseignés';

        guestCheckIn.textContent = guest.checked_in
            ? `Pointé le ${guest.checked_in_at_label}${guest.checked_in_by ? ` par ${guest.checked_in_by}` : ''}`
            : 'Pas encore pointé';

        confirmButton.hidden = !requiresConfirmation;
        undoButton.hidden = !checkedIn;
        nextButton.hidden = false;

        updateCounters(data.counters);

        result.hidden = false;
        result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function renderInvalid(error) {
        currentGuest = null;

        resultMessage.textContent = error?.message ?? 'Impossible de vérifier ce code. Réessayez.';
        resultMessage.className = 'text-caption text-danger';

        guestName.textContent = 'Code non reconnu';
        guestStatus.textContent = 'Invalide';
        guestStatus.className = 'status-pill status-pill-danger';
        guestSeats.textContent = '—';
        guestNames.textContent = '—';
        guestCheckIn.textContent = 'Aucun pointage enregistré';

        confirmButton.hidden = true;
        undoButton.hidden = true;
        nextButton.hidden = false;

        result.hidden = false;
    }

    function updateCounters(counters) {
        if (!counters) {
            return;
        }

        counterHouseholds.textContent = `${counters.checked_in_households} / ${counters.households}`;
        counterSeats.textContent = `${counters.checked_in_seats} / ${counters.seats}`;
    }

    function pauseCamera() {
        if (scanner.isScanning && !paused) {
            paused = true;
            scanner.pause(true);
        }
    }

    function setCameraStatus(message, isError = false) {
        cameraStatus.textContent = message;
        cameraStatus.className = isError ? 'mt-2 text-caption text-danger' : 'mt-2 text-caption text-ink-muted';
    }

    function setActionsDisabled(disabled) {
        confirmButton.disabled = disabled;
        undoButton.disabled = disabled;
        nextButton.disabled = disabled;
        manualForm.querySelector('button[type="submit"]').disabled = disabled;
    }

    function statusClass(status) {
        return {
            attending: 'status-pill-success',
            declined: 'status-pill-danger',
            pending: 'status-pill-warning',
        }[status] ?? '';
    }

    async function request(url, method, body) {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body === undefined ? null : JSON.stringify(body),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(data.message ?? 'La vérification a échoué. Réessayez.');
            error.status = response.status;

            throw error;
        }

        return data;
    }
}
