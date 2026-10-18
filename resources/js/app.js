// Фронтенд новой версии сайта: без jQuery, только нужное.
// Модули: аналитика, главная (герой A / герой B, планы), мастер.

const fmt = (n) => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const readJson = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
};

/* Аналитика: цели Яндекс.Метрики, если счётчик подключён. Версия героя добавляется к каждому событию. */
const track = (name, params = {}) => {
    const variant = document.querySelector('main[data-hero-variant]')?.dataset.heroVariant;
    const payload = { ...params, ...(variant ? { hero: variant } : {}) };
    if (typeof window.ym === 'function' && window.YM_ID) window.ym(window.YM_ID, 'reachGoal', name, payload);
    if (window.DEBUG_TRACK) console.info('[track]', name, payload);
};
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-track]');
    if (el) track(el.dataset.track, { source: el.dataset.source || '' });
});

/* Сегментный переключатель с едущей таблеткой */
function segment(el, onChange) {
    if (!el) return null;
    const btns = [...el.querySelectorAll('button')];
    const pill = el.querySelector('.pill');
    const set = (value) => {
        const i = btns.findIndex((b) => (b.dataset.freq ?? b.dataset.mode) === value);
        btns.forEach((b, j) => b.setAttribute('aria-pressed', String(i === j)));
        if (pill) { pill.style.opacity = i < 0 ? '0' : '1'; if (i >= 0) pill.style.transform = 'translateX(' + (i * 100) + '%)'; }
    };
    btns.forEach((b) => b.addEventListener('click', () => { const v = b.dataset.freq ?? b.dataset.mode; set(v); onChange(v, b); }));
    const pressed = btns.find((b) => b.getAttribute('aria-pressed') === 'true');
    if (pressed) set(pressed.dataset.freq ?? pressed.dataset.mode);
    return { set };
}

/* Главная */
function initHome() {
    const data = readJson('site-data');
    if (!data) return;
    const pricing = data.pricing;
    const planCards = [...document.querySelectorAll('#planList .plan')];
    const plansTitle = document.getElementById('plansTitle');
    const freqNote = document.getElementById('freqNote');
    const cta = document.getElementById('heroCta');
    const state = { freq: 'biweekly', mode: null };

    const modeCopy = {
        home: { title: 'Планы для дома', cta: 'Собрать подписку домой', hint: 'Вам подойдёт план «Просто так»' },
        gift: { title: 'Планы в подарок', cta: 'Собрать подарок', hint: 'Вам подойдёт план «С любовью»' }
    };

    const renderPlans = (opts = {}) => {
        const f = pricing[state.freq];
        planCards.forEach((card) => {
            const plan = data.plans.find((p) => String(p.id) === card.dataset.id);
            if (!plan) return;
            const first = plan.price * f.firstPaid, monthly = plan.price * f.perMonth, strike = plan.price * f.firstDeliveries;
            const firstEl = card.querySelector('[data-first]');
            firstEl.innerHTML = '<s>' + fmt(strike) + ' ₽</s>' + fmt(first) + ' ₽';
            if (opts.anim && !reduced) { firstEl.classList.remove('price-anim'); void firstEl.offsetWidth; firstEl.classList.add('price-anim'); }
            card.querySelector('[data-promo]').textContent = f.promo;
            card.querySelector('[data-monthly]').textContent = fmt(monthly) + ' ₽ / мес';
            const sel = state.mode !== null && card.dataset.scenario === state.mode;
            card.classList.toggle('selected', sel);
            let chip = card.querySelector('.chip');
            if (sel && !chip) { chip = document.createElement('span'); chip.className = 'chip'; chip.textContent = 'Под ваш выбор'; card.insertBefore(chip, card.querySelector('.in')); }
            if (!sel && chip) chip.remove();
            const link = card.querySelector('[data-cta]');
            const url = new URL(link.href, location.origin);
            url.searchParams.set('freq', state.freq);
            if (state.mode) url.searchParams.set('for', state.mode); else url.searchParams.delete('for');
            link.href = url.pathname + url.search;
            link.classList.toggle('secondary', !sel);
            link.dataset.track = 'plan_cta_click'; link.dataset.source = 'plan:' + plan.id;
        });
        const list = document.getElementById('planList');
        if (opts.scroll && list && list.scrollWidth > list.clientWidth) {
            const el = list.querySelector('.plan.selected');
            if (el) list.scrollTo({ left: el.offsetLeft - 16, behavior: reduced ? 'auto' : 'smooth' });
        }
    };

    const heroA = {
        title: document.getElementById('heroTitle'), lead: document.getElementById('heroLead'),
        img: document.getElementById('heroImg'), blob: document.getElementById('heroBlob'),
        copy: {
            home: { title: 'Пусть в вазе всегда <em>что\u2011то стоит</em>', lead: 'Свежие цветы приходят домой сами: раз в неделю, в две или в месяц. Без упаковки, сразу в вазу. Пауза в один тап.', img: '/images/site/hero-home.jpg', alt: 'Цветы в стеклянной вазе на деревянном столе', pos: '50% 60%', blob: 'var(--color-blob-sand)' },
            gift: { title: 'Чтобы повод был <em>не нужен</em>', lead: 'Букет любимому человеку приходит сам, с открыткой от вас: раз в две недели или в месяц. Настроили один раз, остальное помним мы.', img: '/images/site/hero-gift.jpg', alt: 'Букет в розовой упаковке в руках', pos: '50% 35%', blob: 'var(--color-blob-pink)' }
        }
    };
    const retrigger = (el, cls) => { if (!el || reduced) return; el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); };

    let heroSeg = null, modeSeg = null;
    const cards = [...document.querySelectorAll('#modeCards .choice')];
    const hint = document.getElementById('chooseHint');

    const applyMode = (mode, opts = {}) => {
        state.mode = mode;
        // герой A: тексты и фото
        if (document.querySelector('.hero-a') && mode && heroA.copy[mode]) {
            const c = heroA.copy[mode];
            heroA.title.innerHTML = c.title; heroA.lead.textContent = c.lead;
            heroA.img.src = c.img; heroA.img.alt = c.alt; heroA.img.style.objectPosition = c.pos; heroA.blob.style.background = c.blob;
            retrigger(heroA.title, 'rise'); retrigger(heroA.lead, 'rise'); retrigger(heroA.img, 'photo-in');
        }
        // герой B: карточки и подсказка
        cards.forEach((c) => c.setAttribute('aria-checked', String(c.dataset.mode === mode)));
        if (hint) hint.innerHTML = mode ? modeCopy[mode].hint + ' · <a href="#plans">к планам</a>' : '';
        if (cta) {
            const url = new URL(cta.href, location.origin);
            if (mode) url.searchParams.set('for', mode); else url.searchParams.delete('for');
            cta.href = url.pathname + url.search;
            if (document.querySelector('.hero-b')) cta.textContent = mode ? modeCopy[mode].cta : 'Собрать подписку';
        }
        if (plansTitle) plansTitle.textContent = mode ? modeCopy[mode].title : 'Планы';
        heroSeg?.set(mode); modeSeg?.set(mode);
        try { if (mode) localStorage.setItem('cvf.for', mode); } catch (e) {}
        if (opts.fromUser) track('hero_mode_select', { mode, position: opts.position || 'hero' });
        renderPlans({ scroll: !!opts.fromUser });
    };

    heroSeg = segment(document.getElementById('heroSeg'), (v) => applyMode(v, { fromUser: true, position: 'hero' }));
    modeSeg = segment(document.getElementById('modeSeg'), (v) => applyMode(v, { fromUser: true, position: 'plans' }));
    cards.forEach((c) => c.addEventListener('click', () => applyMode(c.dataset.mode, { fromUser: true, position: 'hero' })));
    segment(document.getElementById('freqSeg'), (v) => { state.freq = v; if (freqNote) freqNote.textContent = pricing[v].note; renderPlans({ anim: true }); });

    // начальное состояние: A стартует с «домой», B без выбора; сохранённый выбор побеждает
    let initial = data.variant === 'a' ? 'home' : null;
    try { const s = localStorage.getItem('cvf.for'); if (s === 'home' || s === 'gift') initial = s; } catch (e) {}
    applyMode(initial);

    // просмотр секции планов
    const plansSection = document.getElementById('plans');
    if (plansSection && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => entries.forEach((en) => { if (en.intersectionRatio >= 0.5) { track('plans_view'); io.disconnect(); } }), { threshold: [0.5] });
        io.observe(plansSection);
    }
}

/* Мастер, шаги 1–2 */
function initWizard() {
    const data = readJson('wizard-data');
    if (!data) return;
    const pricing = data.pricing;
    const $ = (id) => document.getElementById(id);
    const st = { budget: Number(data.budget) || 3000, freq: data.frequency, step: 1, styles: [] };
    const shortNotes = {
        weekly: '4 букета в месяц. День выберете на шаге «Доставка».',
        biweekly: '2 букета в месяц. День выберете на шаге «Доставка».',
        monthly: '1 букет в месяц. В первый месяц привезём два.'
    };
    const barSub = { weekly: '4 букета по цене 3', biweekly: '2 букета по цене 1', monthly: '2 букета по цене 1' };
    const hint = (b) => b < 2000 ? 'Компактный букет из 5–7 сезонных стеблей.' : b < 4000 ? 'Полный букет: розы, эустома, зелень. Самый частый выбор.' : b < 8000 ? 'Большая авторская композиция, пионовидные розы, редкая зелень.' : 'Статусная композиция. Флорист свяжется, чтобы уточнить вкус.';
    const tier = (b) => b < 2000 ? 0 : b < 4000 ? 1 : b < 8000 ? 2 : 3;
    const flip = (el) => { if (!el || reduced) return; el.classList.remove('price-anim'); void el.offsetWidth; el.classList.add('price-anim'); };

    const render = (anim) => {
        const b = st.budget, f = pricing[st.freq], pct = ((b - 1000) / 14000) * 100;
        $('budget').style.setProperty('--fill', pct.toFixed(1) + '%');
        $('budget').setAttribute('aria-valuetext', fmt(b) + ' рублей');
        $('budgetText').textContent = fmt(b) + ' ₽'; $('budgetHint').textContent = hint(b);
        $('sBudget').textContent = fmt(b) + ' ₽'; $('sFreq').textContent = f.label;
        $('sFirst').textContent = fmt(b * f.firstPaid) + ' ₽'; $('sPromo').textContent = f.promo.replace('Первый месяц: ', '');
        $('sMonthly').textContent = fmt(b * f.perMonth) + ' ₽ / мес';
        $('bFirst').textContent = fmt(b * f.firstPaid) + ' ₽'; $('bSub').textContent = barSub[st.freq] + ' · дальше ' + fmt(b * f.perMonth) + ' ₽/мес';
        $('freqNote').textContent = shortNotes[st.freq];
        document.querySelectorAll('.preview img').forEach((i) => i.classList.toggle('on', Number(i.dataset.tier) === tier(b)));
        $('previewCap').textContent = 'Примерно так за ' + fmt(b) + ' ₽';
        $('sStyleRow').hidden = st.step < 2; $('sStyle').textContent = st.styles.length ? st.styles.join(', ') : 'доверимся флористу';
        if (anim) { flip($('sFirst')); flip($('bFirst')); }
    };

    $('budget').addEventListener('input', (e) => { st.budget = Number(e.target.value); render(false); });
    segment($('freqSeg'), (v) => { st.freq = v; render(true); });
    document.querySelectorAll('#styles .style').forEach((s) => s.addEventListener('click', () => {
        s.setAttribute('aria-pressed', String(s.getAttribute('aria-pressed') !== 'true'));
        st.styles = [...document.querySelectorAll('#styles .style[aria-pressed="true"]')].map((x) => x.dataset.name); render(false);
    }));
    document.querySelectorAll('#tags button').forEach((t) => t.addEventListener('click', () => t.setAttribute('aria-pressed', String(t.getAttribute('aria-pressed') !== 'true'))));

    const go = (step) => {
        st.step = step; $('step1').hidden = step !== 1; $('step2').hidden = step !== 2;
        if (step === 2) document.querySelectorAll('#styles img[data-src]').forEach((i) => { i.src = i.dataset.src; i.removeAttribute('data-src'); });
        [...$('progress').children].forEach((i, k) => i.classList.toggle('on', k < step)); $('progress').setAttribute('aria-valuenow', step);
        $('stepLabel').textContent = 'Шаг ' + step + ' из 4';
        const sec = step === 1 ? $('step1') : $('step2');
        if (!reduced) { sec.style.animation = 'none'; void sec.offsetWidth; sec.style.animation = 'rise .25s var(--ease-enter) both'; }
        window.scrollTo({ top: 0, behavior: 'auto' }); sec.querySelector('h1').focus({ preventScroll: true });
        track('wizard_step', { step }); render(false);
    };
    $('next1').addEventListener('click', () => go(2));
    $('back2').addEventListener('click', (e) => { e.preventDefault(); go(1); });
    $('backLink').addEventListener('click', (e) => { if (st.step > 1) { e.preventDefault(); go(st.step - 1); } });
    $('next2').addEventListener('click', () => { track('wizard_step', { step: 3 }); window.alert('Шаг 3 «Доставка» появится на следующем этапе разработки.'); });
    $('barNext').addEventListener('click', () => (st.step === 1 ? go(2) : $('next2').click()));

    track('wizard_start', { source: data.planId ? 'plan' : 'link', for: data.for || '' });
    render(false);
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('site-data')) initHome();
    if (document.getElementById('wizard')) initWizard();
});
