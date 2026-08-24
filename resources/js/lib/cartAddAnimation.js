const FLY_DURATION_MS = 750;

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2;
}

function quadraticBezier(p0, p1, p2, t) {
    const inverse = 1 - t;

    return inverse * inverse * p0 + 2 * inverse * t * p1 + t * t * p2;
}

export function getNavbarCartTarget() {
    return document.querySelector('[data-cart-target="navbar"]');
}

function waitForNavbarCartTarget(maxWaitMs = 600) {
    return new Promise((resolve) => {
        const startedAt = performance.now();

        const check = () => {
            const target = getNavbarCartTarget();

            if (target) {
                const rect = target.getBoundingClientRect();

                if (rect.width > 0 && rect.height > 0) {
                    resolve(target);
                    return;
                }
            }

            if (performance.now() - startedAt >= maxWaitMs) {
                resolve(null);
                return;
            }

            requestAnimationFrame(check);
        };

        requestAnimationFrame(check);
    });
}

export function bumpNavbarCart() {
    const target = getNavbarCartTarget();

    if (!target) {
        return;
    }

    target.classList.remove('nav-cart-bump');
    void target.offsetWidth;
    target.classList.add('nav-cart-bump');
}

let audioContext = null;

function getAudioContext() {
    if (audioContext) {
        return audioContext;
    }

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) {
        return null;
    }

    audioContext = new AudioContextClass();

    return audioContext;
}

export function playCartAddSound() {
    if (prefersReducedMotion()) {
        return;
    }

    try {
        const context = getAudioContext();

        if (!context) {
            return;
        }

        const run = () => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, context.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(520, context.currentTime + 0.08);

            gain.gain.setValueAtTime(0.0001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.22, context.currentTime + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.12);

            oscillator.connect(gain);
            gain.connect(context.destination);

            oscillator.start(context.currentTime);
            oscillator.stop(context.currentTime + 0.13);
        };

        if (context.state === 'suspended') {
            context.resume().then(run).catch(() => undefined);
            return;
        }

        run();
    } catch {
        // Ignore audio failures — cart must keep working.
    }
}

export async function flyProductToCart({ sourceElement, imageUrl }) {
    if (prefersReducedMotion()) {
        await waitForNavbarCartTarget();
        bumpNavbarCart();
        return;
    }

    const target = await waitForNavbarCartTarget();

    if (!sourceElement || !target) {
        bumpNavbarCart();
        return;
    }

    const sourceRect = sourceElement.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();

    const startX = sourceRect.left + sourceRect.width / 2;
    const startY = sourceRect.top + sourceRect.height / 2;
    const endX = targetRect.left + targetRect.width / 2;
    const endY = targetRect.top + targetRect.height / 2;
    const controlX = (startX + endX) / 2;
    const controlY = Math.min(startY, endY) - Math.max(72, Math.abs(endX - startX) * 0.18);

    const flyElement = document.createElement('div');
    flyElement.className = 'cart-fly-clone';
    flyElement.setAttribute('aria-hidden', 'true');

    if (imageUrl) {
        const image = document.createElement('img');
        image.src = imageUrl;
        image.alt = '';
        flyElement.appendChild(image);
    } else {
        flyElement.classList.add('cart-fly-clone-fallback');
    }

    document.body.appendChild(flyElement);

    const size = Math.min(64, Math.max(44, sourceRect.width * 0.22));
    flyElement.style.width = `${size}px`;
    flyElement.style.height = `${size}px`;
    flyElement.style.left = `${startX}px`;
    flyElement.style.top = `${startY}px`;

    return new Promise((resolve) => {
        const startedAt = performance.now();

        const tick = (now) => {
            const progress = Math.min(1, (now - startedAt) / FLY_DURATION_MS);
            const eased = easeInOutCubic(progress);
            const x = quadraticBezier(startX, controlX, endX, eased);
            const y = quadraticBezier(startY, controlY, endY, eased);
            const scale = 1 - eased * 0.65;
            const opacity = 1 - eased * 0.2;

            flyElement.style.transform = `translate(calc(-50% + ${x - startX}px), calc(-50% + ${y - startY}px)) scale(${scale})`;
            flyElement.style.opacity = String(opacity);

            if (progress < 1) {
                requestAnimationFrame(tick);
                return;
            }

            flyElement.remove();
            bumpNavbarCart();
            resolve();
        };

        requestAnimationFrame(tick);
    });
}
