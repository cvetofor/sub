// Общие помощники для страниц сайта: форматирование, аналитика, запросы.

export const fmt = (n) => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
export const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export const readJson = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
};

/* Аналитика.
   1) Своя база: POST /frontend-api/track, по ней админка считает воронку A/B-теста.
   2) Яндекс.Метрика: цели reachGoal и электронная коммерция через dataLayer (impressions, detail, add, purchase). */
export const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
export const track = (name, params = {}) => {
    const main = document.querySelector('main[data-hero-variant]');
    const variant = main?.dataset.heroVariant;
    const design = main?.dataset.design;
    const payload = { ...params, ...(variant ? { hero: variant } : {}), ...(design ? { design } : {}) };
    if (typeof window.ym === 'function' && window.YM_ID) window.ym(window.YM_ID, 'reachGoal', name, payload);
    if (window.TRACK_URL) {
        const body = JSON.stringify({ event: name, step: params.step, plan_id: params.plan_id, mode: params.mode, frequency: params.frequency, source: params.source });
        try {
            fetch(window.TRACK_URL, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, body }).catch(() => {});
        } catch (e) { /* аналитика не должна ломать страницу */ }
    }
    if (window.DEBUG_TRACK) console.info('[track]', name, payload);
};
export const ecommerce = (action, payload) => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: { currencyCode: 'RUB', [action]: payload } });
    if (window.DEBUG_TRACK) console.info('[ecommerce]', action, payload);
};
export const product = (p, extra = {}) => ({ id: String(p.id), name: p.name, category: 'Подписка на цветы', price: Number(p.price), ...extra });

/* Запрос JSON с CSRF. Возвращает тело ответа и признак ok (HTTP 2xx и ok в теле). */
export async function postJson(url, body) {
    const res = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) });
    let json = {};
    try { json = await res.json(); } catch (e) { json = {}; }
    return { ...json, ok: res.ok && json.ok !== false, status: res.status };
}
export const firstError = (r) => (r && r.errors && Object.values(r.errors)[0] && Object.values(r.errors)[0][0]) || '';
