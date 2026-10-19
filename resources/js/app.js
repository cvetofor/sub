// Фронтенд новой версии сайта: без jQuery, только нужное.
// Модули: аналитика, главная (герой A / герой B, планы), мастер.

const fmt = (n) => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const readJson = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
};

/* Аналитика.
   1) Своя база: POST /frontend-api/track, по ней админка считает воронку A/B-теста.
   2) Яндекс.Метрика: цели reachGoal и электронная коммерция через dataLayer (impressions, detail, add, purchase). */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const track = (name, params = {}) => {
    const variant = document.querySelector('main[data-hero-variant]')?.dataset.heroVariant;
    const payload = { ...params, ...(variant ? { hero: variant } : {}) };
    if (typeof window.ym === 'function' && window.YM_ID) window.ym(window.YM_ID, 'reachGoal', name, payload);
    if (window.TRACK_URL) {
        const body = JSON.stringify({ event: name, step: params.step, plan_id: params.plan_id, mode: params.mode, frequency: params.frequency, source: params.source });
        try {
            fetch(window.TRACK_URL, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, body }).catch(() => {});
        } catch (e) { /* аналитика не должна ломать страницу */ }
    }
    if (window.DEBUG_TRACK) console.info('[track]', name, payload);
};
const ecommerce = (action, payload) => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: { currencyCode: 'RUB', [action]: payload } });
    if (window.DEBUG_TRACK) console.info('[ecommerce]', action, payload);
};
const product = (p, extra = {}) => ({ id: String(p.id), name: p.name, category: 'Подписка на цветы', price: Number(p.price), ...extra });

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
            link.removeAttribute('data-track');
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

    // электронная коммерция: показ планов и выбор плана
    ecommerce('impressions', data.plans.map((p, i) => product(p, { list: 'Главная / планы', position: i + 1 })));
    planCards.forEach((card) => card.querySelector('[data-cta]')?.addEventListener('click', () => {
        const plan = data.plans.find((p) => String(p.id) === card.dataset.id);
        if (!plan) return;
        ecommerce('detail', { products: [product(plan, { variant: pricing[state.freq].label })] });
        track('plan_cta_click', { plan_id: plan.id, frequency: state.freq, mode: state.mode || undefined, source: 'plan' });
    }));

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

    let render = (anim) => {
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

    const cartProduct = () => {
        const f = pricing[st.freq];
        return data.planId
            ? { id: String(data.planId), name: data.planName || 'План', category: 'Подписка на цветы', price: st.budget, quantity: f.firstPaid, variant: f.label }
            : { id: 'custom', name: 'Своя подписка', category: 'Подписка на цветы', price: st.budget, quantity: f.firstPaid, variant: f.label };
    };

    /* Шаг 3: доставка */
    const dayEvery = ['', 'каждый понедельник', 'каждый вторник', 'каждую среду', 'каждый четверг', 'каждую пятницу', 'каждую субботу', 'каждое воскресенье'];
    const dayShort = ['', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'];
    st.day = 6; st.windowId = null; st.additions = [];
    const radios = (el, attr, onChange) => {
        if (!el) return;
        const btns = [...el.querySelectorAll('[role="radio"]')];
        btns.forEach((b) => b.addEventListener('click', () => { btns.forEach((x) => x.setAttribute('aria-checked', String(x === b))); onChange(b.dataset[attr]); }));
        const on = btns.find((b) => b.getAttribute('aria-checked') === 'true'); if (on) onChange(on.dataset[attr]);
    };
    radios($('days'), 'day', (v) => { st.day = Number(v); $('dayNote').textContent = 'Привозим ' + dayEvery[st.day] + ', в выбранное окно.'; render(false); });
    radios($('windows'), 'id', (v) => { st.windowId = Number(v); render(false); });
    document.querySelectorAll('#additions input').forEach((c) => c.addEventListener('change', () => {
        st.additions = [...document.querySelectorAll('#additions input:checked')].map((x) => ({ id: Number(x.value), price: Number(x.dataset.price), every: x.dataset.every === '1', name: x.closest('.opt').querySelector('b').textContent }));
        render(true);
    }));
    const extras = () => {
        const f = pricing[st.freq];
        const every = st.additions.filter((a) => a.every).reduce((n, a) => n + a.price, 0);
        const once = st.additions.filter((a) => !a.every).reduce((n, a) => n + a.price, 0);
        return { first: every * f.firstPaid + once, monthly: every * f.perMonth };
    };
    // Ближайшая дата выбранного дня недели, не раньше чем через два дня: флористу нужно время на закупку
    const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    const nextDate = (day) => { const d = new Date(); d.setDate(d.getDate() + 2); const js = day % 7; while (d.getDay() !== js) d.setDate(d.getDate() + 1); return dayShort[day] + ', ' + d.getDate() + ' ' + months[d.getMonth()]; };
    const windowLabel = () => document.querySelector('#windows [aria-checked="true"]')?.textContent || '';
    const setErr = (id, msg) => { const e = $(id); if (!e) return; e.hidden = !msg; e.textContent = msg || ''; const inp = e.previousElementSibling; if (inp && inp.tagName === 'INPUT') inp.setAttribute('aria-invalid', msg ? 'true' : 'false'); };
    const phoneOk = (v) => /^(\+7|7|8)?[\s-]?\(?9\d{2}\)?[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2}$/.test(v.trim()) || /^9\d{9}$/.test(v.replace(/\D/g, ''));
    const validStep3 = () => {
        let ok = true;
        if ($('address').value.trim().length < 5) { setErr('errAddress', 'Укажите адрес: улица, дом, квартира.'); ok = false; } else setErr('errAddress', '');
        const rp = $('receivingPhone');
        if (rp && !$('giftFields').hidden && rp.value.trim() && !phoneOk(rp.value)) { setErr('errReceivingPhone', 'Телефон в формате +7 912 345-67-89.'); ok = false; } else setErr('errReceivingPhone', '');
        if (!ok) { const bad = document.querySelector('#step3 [aria-invalid="true"]'); bad?.focus(); }
        return ok;
    };

    /* Переопределяем render: добавляем доставку и дополнения в сводку */
    const baseRender = render;
    const render2 = (anim) => {
        baseRender(anim);
        const f = pricing[st.freq], ex = extras();
        const first = st.budget * f.firstPaid + ex.first, monthly = st.budget * f.perMonth + ex.monthly;
        $('sFirst').textContent = fmt(first) + ' ₽'; $('sMonthly').textContent = fmt(monthly) + ' ₽ / мес';
        $('bFirst').textContent = fmt(first) + ' ₽'; $('bSub').textContent = barSub[st.freq] + ' · дальше ' + fmt(monthly) + ' ₽/мес';
        const delivery = st.step >= 3 && $('address').value.trim() ? dayShort[st.day] + (windowLabel() ? ', ' + windowLabel() : '') : '';
        $('sDeliveryRow').hidden = !delivery; $('sDelivery').textContent = delivery;
        if ($('sNextDate') && st.step >= 3) $('sNextDate').textContent = nextDate(st.day);
        $('sExtrasRow').hidden = !st.additions.length; $('sExtras').textContent = st.additions.map((a) => a.name).join(', ');
        const ms = $('mobileSummary');
        if (ms) {
            const put = (k, v) => { const el = ms.querySelector('[data-sum="' + k + '"]'); if (el) el.textContent = v; };
            put('budget', fmt(st.budget) + ' ₽'); put('freq', f.label); put('delivery', delivery || '—'); put('extras', st.additions.map((a) => a.name).join(', '));
            ms.querySelector('[data-sum-row="extras"]').hidden = !st.additions.length;
            put('first', fmt(first) + ' ₽'); put('promo', f.promo.replace('Первый месяц: ', '')); put('monthly', fmt(monthly) + ' ₽ / мес');
        }
        if ($('payBtn')) $('payBtn').textContent = 'Перейти к оплате · ' + fmt(first) + ' ₽';
        if (st.step === 4) $('barNext').textContent = 'Оплатить';
        else if (st.step === 3) $('barNext').textContent = 'Дальше';
        else $('barNext').textContent = 'Дальше';
    };
    render = render2; // eslint-disable-line no-func-assign

    const go = (step) => {
        if (step === 2 && st.step === 1) ecommerce('add', { products: [cartProduct()] });
        st.step = step;
        [1, 2, 3, 4].forEach((n) => { const sec = $('step' + n); if (sec) sec.hidden = step !== n; });
        if (step === 2) document.querySelectorAll('#styles img[data-src]').forEach((i) => { i.src = i.dataset.src; i.removeAttribute('data-src'); });
        [...$('progress').children].forEach((i, k) => i.classList.toggle('on', k < step)); $('progress').setAttribute('aria-valuenow', step);
        $('stepLabel').textContent = 'Шаг ' + step + ' из 4';
        const sec = $('step' + step);
        if (!reduced) { sec.style.animation = 'none'; void sec.offsetWidth; sec.style.animation = 'rise .25s var(--ease-enter) both'; }
        window.scrollTo({ top: 0, behavior: 'auto' }); sec.querySelector('h1').focus({ preventScroll: true });
        track('wizard_step', { step, plan_id: data.planId || undefined, frequency: st.freq, mode: data.for || undefined }); render(false);
    };
    $('next1').addEventListener('click', () => go(2));
    $('back2').addEventListener('click', (e) => { e.preventDefault(); go(1); });
    $('next2').addEventListener('click', () => go(3));
    $('back3').addEventListener('click', (e) => { e.preventDefault(); go(2); });
    $('next3').addEventListener('click', () => { if (validStep3()) go(4); });
    $('back4').addEventListener('click', (e) => { e.preventDefault(); go(3); });
    $('backLink').addEventListener('click', (e) => { if (st.step > 1) { e.preventDefault(); go(st.step - 1); } });
    $('address').addEventListener('input', () => render(false));

    /* Шаг 4: вход по коду и оплата */
    const auth = initCodeAuth({
        urls: data.urls,
        nameInput: $('senderName'),
        onDone: (user) => {
            $('authForm').hidden = true; $('authDone').hidden = false;
            $('whoName').textContent = user.name; $('whoPhone').textContent = user.phone;
            $('authBox').dataset.logged = '1';
        }
    });
    const payError = (msg) => { $('payError').hidden = !msg; $('payError').textContent = msg || ''; };
    let paying = false;
    const pay = async () => {
        if (paying) return;
        payError('');
        if ($('authBox').dataset.logged !== '1') {
            if (!auth.hasCode()) { await auth.sendCode(); return; }
            const ok = await auth.verify(); if (!ok) return;
        }
        const name = ($('whoName').textContent || $('senderName').value).trim();
        if (name.length < 2) { payError('Укажите имя.'); return; }
        paying = true; $('payBtn').setAttribute('aria-busy', 'true'); $('barNext').setAttribute('aria-busy', 'true');
        const body = {
            plan_id: data.planId || null, budget: data.planId ? null : st.budget, frequency: st.freq, for: data.for || null,
            styles: st.styles, tags: [...document.querySelectorAll('#tags button[aria-pressed="true"]')].map((t) => t.textContent),
            address: $('address').value.trim(), delivery_day: st.day, time_delivery_id: st.windowId,
            option_ids: st.additions.map((a) => a.id), sender_name: name,
            receiving_name: $('receivingName') && !$('giftFields').hidden ? $('receivingName').value.trim() || null : null,
            receiving_phone: $('receivingPhone') && !$('giftFields').hidden ? $('receivingPhone').value.trim() || null : null,
            card_text: $('cardText') && !$('giftFields').hidden ? $('cardText').value.trim() || null : null,
            comment: $('comment').value.trim() || null
        };
        try {
            const r = await postJson(data.urls.store, body);
            if (!r.ok) { payError(r.message || firstError(r) || 'Не удалось оформить. Проверьте поля.'); return; }
            track('payment_redirect', { plan_id: data.planId || undefined, frequency: st.freq, mode: data.for || undefined });
            window.location.assign(r.payment_url);
        } catch (e) {
            payError('Нет связи с сервером. Попробуйте ещё раз.');
        } finally {
            paying = false; $('payBtn').removeAttribute('aria-busy'); $('barNext').removeAttribute('aria-busy');
        }
    };
    $('payBtn').addEventListener('click', pay);
    $('barNext').addEventListener('click', () => { if (st.step === 1) go(2); else if (st.step === 2) go(3); else if (st.step === 3) $('next3').click(); else pay(); });

    track('wizard_start', { source: data.planId ? 'plan' : 'link', plan_id: data.planId || undefined, frequency: st.freq, mode: data.for || undefined });
    render(false);
}

/* Запрос JSON с CSRF. Возвращает тело ответа и признак ok (HTTP 2xx и ok в теле). */
async function postJson(url, body) {
    const res = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) });
    let json = {};
    try { json = await res.json(); } catch (e) { json = {}; }
    return { ...json, ok: res.ok && json.ok !== false, status: res.status };
}
const firstError = (r) => (r && r.errors && Object.values(r.errors)[0] && Object.values(r.errors)[0][0]) || '';

/* Вход по коду из СМС: общий для шага 4 мастера и страницы входа.
   Ожидает элементы senderPhone, sendCodeBtn, codeRow, smsCode, verifyBtn, resendBtn, errPhone, errCode, debugCode. */
function initCodeAuth({ urls, nameInput, onDone }) {
    const $ = (id) => document.getElementById(id);
    const phoneEl = $('senderPhone'), sendBtn = $('sendCodeBtn'), codeRow = $('codeRow'), codeEl = $('smsCode'), verifyBtn = $('verifyBtn'), resendBtn = $('resendBtn');
    if (!phoneEl || !sendBtn) return { hasCode: () => false, sendCode: async () => false, verify: async () => false };
    const setErr = (id, msg) => { const e = $(id); if (!e) return; e.hidden = !msg; e.textContent = msg || ''; };
    let sentTo = null, timer = null;
    const countdown = (sec) => {
        clearInterval(timer); let left = sec; resendBtn.disabled = true;
        const tick = () => { resendBtn.textContent = left > 0 ? 'Отправить ещё раз через ' + left + ' с' : 'Отправить ещё раз'; if (left <= 0) { clearInterval(timer); resendBtn.disabled = false; } left--; };
        tick(); timer = setInterval(tick, 1000);
    };
    const sendCode = async () => {
        setErr('errPhone', ''); setErr('errCode', '');
        if (nameInput && nameInput.value.trim().length < 2) { setErr('errName', 'Как к вам обращаться?'); nameInput.focus(); return false; }
        if (nameInput) setErr('errName', '');
        if (phoneEl.value.replace(/\D/g, '').length < 10) { setErr('errPhone', 'Введите номер мобильного телефона.'); phoneEl.focus(); return false; }
        sendBtn.setAttribute('aria-busy', 'true');
        try {
            const r = await postJson(urls.code, { phone: phoneEl.value });
            if (!r.ok) { setErr('errPhone', r.message || firstError(r) || 'Не удалось отправить код.'); if (r.status === 429) { codeRow.hidden = false; countdown(r.retry_after || 60); } return false; }
            sentTo = r.phone; phoneEl.value = r.pretty || phoneEl.value;
            codeRow.hidden = false; sendBtn.hidden = true; codeEl.value = ''; codeEl.focus(); countdown(r.retry_after || 60);
            if (r.debug_code && $('debugCode')) { $('debugCode').hidden = false; $('debugCode').textContent = 'Тестовый стенд: код ' + r.debug_code; }
            return true;
        } catch (e) { setErr('errPhone', 'Нет связи с сервером.'); return false; } finally { sendBtn.removeAttribute('aria-busy'); }
    };
    const verify = async () => {
        setErr('errCode', '');
        if (!/^\d{4}$/.test(codeEl.value.trim())) { setErr('errCode', 'Введите 4 цифры из СМС.'); codeEl.focus(); return false; }
        verifyBtn.setAttribute('aria-busy', 'true');
        try {
            const r = await postJson(urls.verify, { phone: sentTo || phoneEl.value, code: codeEl.value.trim(), name: nameInput ? nameInput.value.trim() || null : null });
            if (!r.ok) { setErr('errCode', r.message || firstError(r) || 'Код не подошёл.'); codeEl.select(); return false; }
            if (r.csrf) { const m = document.querySelector('meta[name="csrf-token"]'); if (m) m.content = r.csrf; }
            onDone(r.user, r);
            return true;
        } catch (e) { setErr('errCode', 'Нет связи с сервером.'); return false; } finally { verifyBtn.removeAttribute('aria-busy'); }
    };
    sendBtn.addEventListener('click', sendCode);
    resendBtn.addEventListener('click', sendCode);
    verifyBtn.addEventListener('click', verify);
    codeEl.addEventListener('input', () => { codeEl.value = codeEl.value.replace(/\D/g, '').slice(0, 4); if (codeEl.value.length === 4) verify(); });
    codeEl.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); verify(); } });
    phoneEl.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); sendCode(); } });
    return { hasCode: () => sentTo !== null, sendCode, verify };
}

/* Страница входа */
function initLogin() {
    const data = readJson('login-data');
    if (!data) return;
    initCodeAuth({ urls: data.urls, nameInput: null, onDone: (user, r) => window.location.assign((r && r.redirect) || data.redirect) });
}

/* Страница успешной оплаты: покупка в электронную коммерцию и цель */
function initPurchase() {
    const p = readJson('purchase-data');
    if (!p) return;
    const key = 'cvf.purchase.' + p.id;
    try { if (localStorage.getItem(key)) return; localStorage.setItem(key, '1'); } catch (e) { /* приватный режим */ }
    ecommerce('purchase', { actionField: { id: p.id, revenue: p.revenue }, products: p.products });
    if (typeof window.ym === 'function' && window.YM_ID) {
        const variant = document.querySelector('main[data-hero-variant]')?.dataset.heroVariant;
        window.ym(window.YM_ID, 'reachGoal', 'payment_success', { plan_id: p.plan_id, frequency: p.frequency, revenue: p.revenue, ...(variant ? { hero: variant } : {}) });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('site-data')) initHome();
    if (document.getElementById('wizard')) initWizard();
    if (document.getElementById('loginPage')) initLogin();
    if (document.getElementById('paymentResult')) initPurchase();
});
