// Opens an admin-configured ad link in a new tab on the visitor's next click,
// once every `frequency` page views and no more often than `cooldownMinutes`.
//
// This has to run client-side: window.open() is blocked unless it runs inside
// a real user gesture.

const VIEWS_KEY = 'bdgAdViews';
const FIRED_AT_KEY = 'bdgAdFiredAt';

// localStorage throws in some privacy modes; fall back to memory for the visit.
const fallback = new Map();

function readNumber(key) {
    try {
        return Number(localStorage.getItem(key) ?? 0) || 0;
    } catch {
        return Number(fallback.get(key) ?? 0) || 0;
    }
}

function writeNumber(key, value) {
    try {
        localStorage.setItem(key, String(value));
    } catch {
        fallback.set(key, value);
    }
}

function readConfig() {
    const element = document.getElementById('bdg-ad-config');

    if (! element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch {
        return null;
    }
}

let armedUrl = null;

function fire() {
    const url = armedUrl;

    // The listener can outlive its armed state, so never open a tab without a link.
    if (! url) {
        return;
    }

    // Reset before opening: a blocked popup must not leave the visitor armed
    // and firing on every subsequent click.
    armedUrl = null;
    writeNumber(VIEWS_KEY, 0);
    writeNumber(FIRED_AT_KEY, Date.now());

    const opened = window.open(url, '_blank');

    if (opened) {
        opened.opener = null;
    }
}

function onPageView() {
    const config = readConfig();

    if (! config) {
        return;
    }

    const views = readNumber(VIEWS_KEY) + 1;
    writeNumber(VIEWS_KEY, views);

    if (views < config.frequency) {
        return;
    }

    // The counter is deliberately not reset here, so the ad arms on the first
    // page view after the cooldown expires.
    if (Date.now() - readNumber(FIRED_AT_KEY) < config.cooldownMinutes * 60000) {
        return;
    }

    armedUrl = config.url;

    // Capture phase so the ad opens inside the same trusted gesture, and
    // without preventDefault() so the visitor's own click still works.
    document.addEventListener('click', fire, { capture: true, once: true });
}

// Every navigation here is a full document load, so one view per load is exact.
export function startAdRotator() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onPageView, { once: true });

        return;
    }

    onPageView();
}
