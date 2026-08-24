const CELEBRATED_PREFIX = 'pebbles:order-celebrated:';

export function celebrationStorageKey(orderNumber) {
    return `${CELEBRATED_PREFIX}${orderNumber}`;
}

export function hasCelebratedOrder(orderNumber) {
    if (!orderNumber || typeof window === 'undefined') {
        return false;
    }

    try {
        return window.sessionStorage.getItem(celebrationStorageKey(orderNumber)) === '1';
    } catch {
        return false;
    }
}

export function markOrderCelebrated(orderNumber) {
    if (!orderNumber || typeof window === 'undefined') {
        return;
    }

    try {
        window.sessionStorage.setItem(celebrationStorageKey(orderNumber), '1');
    } catch {
        // Ignore storage failures — celebration is cosmetic only.
    }
}

export function shouldCelebrateNewOrder({ celebrate, orderNumber }) {
    if (!celebrate || !orderNumber) {
        return false;
    }

    if (hasCelebratedOrder(orderNumber)) {
        return false;
    }

    markOrderCelebrated(orderNumber);

    return true;
}

export function prefersReducedMotion() {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

let audioContext = null;
let cachedAudio = null;

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

function playSynthSuccessSound() {
    const context = getAudioContext();

    if (!context) {
        return;
    }

    const run = () => {
        const now = context.currentTime;
        const master = context.createGain();
        master.gain.setValueAtTime(0.0001, now);
        master.gain.exponentialRampToValueAtTime(0.22, now + 0.02);
        master.gain.exponentialRampToValueAtTime(0.0001, now + 0.55);
        master.connect(context.destination);

        const tones = [
            { frequency: 659.25, start: 0, duration: 0.16 },
            { frequency: 880, start: 0.1, duration: 0.22 },
        ];

        tones.forEach(({ frequency, start, duration }) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(frequency, now + start);

            gain.gain.setValueAtTime(0.0001, now + start);
            gain.gain.exponentialRampToValueAtTime(0.35, now + start + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + start + duration);

            oscillator.connect(gain);
            gain.connect(master);

            oscillator.start(now + start);
            oscillator.stop(now + start + duration + 0.02);
        });
    };

    if (context.state === 'suspended') {
        context.resume().then(run).catch(() => undefined);
        return;
    }

    run();
}

function getSuccessAudio() {
    if (cachedAudio) {
        return cachedAudio;
    }

    if (typeof window === 'undefined') {
        return null;
    }

    const audio = new Audio('/sounds/order-success.mp3');
    audio.preload = 'auto';
    audio.volume = 0.22;
    cachedAudio = audio;

    return cachedAudio;
}

/**
 * Play a short success chime after an explicit user action (Place Order).
 * Falls back to synthesized tones if the optional local asset is unavailable.
 */
export function playOrderSuccessSound() {
    if (prefersReducedMotion()) {
        return;
    }

    const audio = getSuccessAudio();

    if (audio) {
        audio.currentTime = 0;
        const attempt = audio.play();

        if (attempt && typeof attempt.catch === 'function') {
            attempt.catch(() => {
                playSynthSuccessSound();
            });
            return;
        }
    }

    try {
        playSynthSuccessSound();
    } catch {
        // Ignore audio failures — order success UI must still work.
    }
}

const PARTICLE_COLORS = ['#176b3a', '#2e8b57', '#f97316', '#fb923c', '#fffdf8'];

/**
 * @param {HTMLElement} container
 * @returns {() => void} cleanup
 */
export function spawnOrderSuccessParticles(container) {
    if (prefersReducedMotion() || !container) {
        return () => undefined;
    }

    const particles = [];
    const count = 14;

    for (let index = 0; index < count; index += 1) {
        const particle = document.createElement('span');
        particle.className = 'order-success-particle';
        particle.setAttribute('aria-hidden', 'true');

        const angle = (Math.PI * 2 * index) / count + (Math.random() - 0.5) * 0.35;
        const distance = 42 + Math.random() * 38;
        const size = 4 + Math.random() * 5;
        const delay = 480 + Math.random() * 180;
        const duration = 680 + Math.random() * 420;
        const color = PARTICLE_COLORS[index % PARTICLE_COLORS.length];
        const shape = index % 5 === 0 ? 'star' : index % 3 === 0 ? 'dot' : 'circle';

        particle.style.setProperty('--os-x', `${Math.cos(angle) * distance}px`);
        particle.style.setProperty('--os-y', `${Math.sin(angle) * distance}px`);
        particle.style.setProperty('--os-size', `${size}px`);
        particle.style.setProperty('--os-delay', `${delay}ms`);
        particle.style.setProperty('--os-duration', `${duration}ms`);
        particle.style.setProperty('--os-color', color);
        particle.style.setProperty('--os-rotate', `${Math.round(Math.random() * 180)}deg`);

        if (shape === 'star') {
            particle.dataset.shape = 'star';
        }

        container.appendChild(particle);
        particles.push(particle);
    }

    const timeout = window.setTimeout(() => {
        particles.forEach((particle) => particle.remove());
    }, 1600);

    return () => {
        window.clearTimeout(timeout);
        particles.forEach((particle) => particle.remove());
    };
}
