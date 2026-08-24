const BOOT_LOADER_ID = 'app-boot-loader';
const FADE_MS = 250;

export function dismissAppBootLoader() {
    const bootLoader = document.getElementById(BOOT_LOADER_ID);

    if (!bootLoader || bootLoader.classList.contains('is-hidden')) {
        return;
    }

    bootLoader.classList.add('is-hidden');
    bootLoader.setAttribute('aria-busy', 'false');

    window.setTimeout(() => {
        bootLoader.remove();
    }, FADE_MS);
}
