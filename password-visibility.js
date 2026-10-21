(() => {
    const buttons = document.querySelectorAll('[data-password-visibility]');

    const setVisible = (button, visible) => {
        const input = button.parentElement?.querySelector('input');
        if (!input) return;
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Отпустите, чтобы скрыть пароль' : 'Удерживайте, чтобы показать пароль');
        button.title = visible ? 'Отпустите, чтобы скрыть пароль' : 'Удерживайте, чтобы показать пароль';
    };

    buttons.forEach((button) => {
        button.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            event.preventDefault();
            button.setPointerCapture?.(event.pointerId);
            setVisible(button, true);
        });
        ['pointerup', 'pointercancel', 'lostpointercapture', 'pointerleave'].forEach((eventName) => {
            button.addEventListener(eventName, () => setVisible(button, false));
        });
        button.addEventListener('keydown', (event) => {
            if ((event.key === ' ' || event.key === 'Enter') && !event.repeat) {
                event.preventDefault();
                setVisible(button, true);
            }
        });
        button.addEventListener('keyup', (event) => {
            if (event.key === ' ' || event.key === 'Enter') setVisible(button, false);
        });
        button.addEventListener('blur', () => setVisible(button, false));
        button.addEventListener('click', (event) => event.preventDefault());
    });
})();
