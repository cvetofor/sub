// Квиз: главная и пять вопросов на одной странице. Состояние в одном объекте, экраны переключаются без перезагрузки.
import { fmt, reduced, readJson, track, ecommerce, postJson, firstError } from './shared.js';

const MONTHS = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
const MONTHS_NOM = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
const DAY_IN = ['', 'в понедельник', 'во вторник', 'в среду', 'в четверг', 'в пятницу', 'в субботу', 'в воскресенье'];
const DAY_EACH = ['', 'по понедельникам', 'по вторникам', 'по средам', 'по четвергам', 'по пятницам', 'по субботам', 'по воскресеньям'];
const DAY_NOM = ['', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
const FREQ_TEXT = { weekly: 'каждую неделю', biweekly: 'раз в две недели', monthly: 'раз в месяц' };
const INTERVAL = { weekly: 7, biweekly: 14, monthly: 14 };

const dateText = (d) => d.getDate() + ' ' + MONTHS[d.getMonth()];
const isoDay = (d) => (d.getDay() + 6) % 7 + 1; // 1 = понедельник … 7 = воскресенье
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
const phoneOk = (v) => /^(\+7|7|8)?[\s-]?\(?9\d{2}\)?[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2}$/.test(String(v).trim()) || /^9\d{9}$/.test(String(v).replace(/\D/g, ''));

export function initQuiz() {
    const data = readJson('quiz-data');
    if (!data) return;
    const $ = (id) => document.getElementById(id);
    const pricing = data.pricing;
    const st = {
        step: 0, who: null, freq: null, planId: null, own: '', additions: [], card: '', day: null, winId: null, agree: false, hideSender: false,
    };
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const minLead = Number(data.minLeadDays) || 2;

    /* Первая дата выбранного дня недели не раньше чем через minLead дней: как считает сервер */
    const firstDate = (day) => { let d = addDays(today, minLead); while (isoDay(d) !== day) d = addDays(d, 1); return d; };
    const deliveryDates = (day, freq) => {
        if (!day || !freq) return [];
        const count = pricing[freq].firstDeliveries, step = INTERVAL[freq];
        const out = []; let d = firstDate(day);
        for (let i = 0; i < count; i++) { out.push(d); d = addDays(d, step); }
        return out;
    };
    const nextCharge = () => { const d = new Date(today); d.setMonth(d.getMonth() + 1); return d; };

    /* Выбранный букет: готовый или свой бюджет */
    const plan = () => {
        if (st.planId === 0) {
            const price = Math.max(990, parseInt(String(st.own).replace(/\D/g, ''), 10) || 0);
            return { id: 0, name: 'Свой бюджет', price, image: data.plans[0]?.image };
        }
        const p = data.plans.find((x) => x.id === st.planId);
        return p ? { ...p, name: st.who === 'gift' ? p.nameGift : p.name } : null;
    };
    const adds = () => data.additions.filter((a) => st.additions.includes(a.id));
    const sums = () => {
        const p = plan(); const f = pricing[st.freq || 'weekly'];
        const price = p ? p.price : 0;
        const every = adds().filter((a) => a.every).reduce((n, a) => n + a.price, 0);
        const once = adds().filter((a) => !a.every).reduce((n, a) => n + a.price, 0);
        return {
            f, price,
            first: (price + every) * f.firstPaid + once,
            monthly: (price + every) * f.perMonth,
            strike: price * f.firstDeliveries,
            every, once,
        };
    };
    const ownValid = () => (parseInt(String(st.own).replace(/\D/g, ''), 10) || 0) >= 990;
    const planReady = () => (st.planId && st.planId > 0) || (st.planId === 0 && ownValid());
    const giftReady = () => st.who !== 'gift' || (!$('recPhone').value.trim() || phoneOk($('recPhone').value));
    const whenReady = () => st.agree || (!!st.day && (!!st.winId || !data.windows.length));
    const step4Ready = () => $('address').value.trim().length >= 5 && whenReady() && giftReady();

    /* Календарь: месяц первой доставки, отмечены дни доставок, второй букет кольцом */
    const renderCal = (el, dates, exampleDay) => {
        if (!el) return;
        const list = dates.length ? dates : (exampleDay ? deliveryDates(exampleDay, st.freq || 'weekly') : []);
        const base = list[0] || addDays(today, minLead);
        const y = base.getFullYear(), m = base.getMonth();
        const first = new Date(y, m, 1), daysIn = new Date(y, m + 1, 0).getDate();
        const keys = list.map((d) => d.toDateString());
        let html = '<span class="m">' + MONTHS_NOM[m] + '</span>' + ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'].map((d) => '<span class="h">' + d + '</span>').join('');
        for (let i = 1; i < isoDay(first); i++) html += '<span></span>';
        for (let n = 1; n <= daysIn; n++) {
            const idx = keys.indexOf(new Date(y, m, n).toDateString());
            const cls = idx === 1 ? 'd gift' : (idx >= 0 ? 'd on' : 'd');
            const aria = idx === 1 ? n + ' ' + MONTHS[m] + ', второй букет бесплатно' : (idx >= 0 ? n + ' ' + MONTHS[m] + ', доставка' : '');
            html += '<span class="' + cls + '"' + (aria ? ' aria-label="' + aria + '"' : ' aria-hidden="true"') + '>' + n + '</span>';
        }
        el.innerHTML = html;
    };

    /* Экран 1 */
    const render1 = () => {
        const ready = !!st.who && !!st.freq;
        const forWho = st.who === 'gift' ? ' для получателя' : (st.who === 'home' ? ' для вас' : '');
        const titles = { weekly: '4 букета' + forWho + ', второй бесплатно', biweekly: '2 букета' + forWho + ', второй бесплатно', monthly: '2 букета' + forWho + ', второй бесплатно' };
        const subs = {
            weekly: 'Пример с доставкой по пятницам. День и время выберете на шаге 4.',
            biweekly: 'Пример с доставкой раз в две недели по пятницам. День и время выберете на шаге 4.',
            monthly: 'Второй букет привезём через две недели после первого. Дальше один букет в месяц.',
        };
        $('whoHint').textContent = st.who ? 'можно поменять до оплаты' : 'выберите один вариант';
        $('freqHint').textContent = st.freq ? 'пауза и перенос в кабинете в любой день' : 'выберите, как часто привозить';
        $('s1Title').textContent = st.freq ? titles[st.freq] : 'Выберите, как часто привозить';
        $('s1Sub').textContent = st.freq ? subs[st.freq] : 'Календарь покажет дни доставок.';
        renderCal($('cal1'), [], st.freq ? 5 : null);
        $('next1').disabled = !ready;
        $('hint1').textContent = ready ? 'Цены покажем на следующем шаге' : 'Выберите, кому и как часто';
        bar(st.freq ? titles[st.freq] : 'Кому и как часто', st.freq ? 'Цены на следующем шаге' : 'Выберите оба варианта', !ready);
    };

    /* Экран 2 */
    const render2 = () => {
        const p = plan(); const s = sums();
        document.querySelectorAll('#planList .qz-plan').forEach((b) => {
            const id = Number(b.dataset.id);
            b.setAttribute('aria-checked', String(id === st.planId));
            b.querySelector('.qz-plan-name').textContent = st.who === 'gift' ? b.dataset.nameGift : b.dataset.name;
        });
        $('ownBox').classList.toggle('on', st.planId === 0);
        $('s2Who').textContent = (st.who === 'gift' ? 'В подарок' : 'Себе') + ' · ' + (FREQ_TEXT[st.freq] || '');
        $('s2Name').textContent = p ? p.name + ', ' + fmt(p.price) + ' ₽ за букет' : 'Выберите букет';
        if (p && p.image) $('s2Photo').src = p.image;
        const notes = { weekly: '4 букета, платите за 3. Второй бесплатно.', biweekly: '2 букета, платите за 1. Второй бесплатно.', monthly: '2 букета, платите за 1. Второй привезём через две недели, бесплатно.' };
        $('s2First').textContent = p ? fmt(s.first) + ' ₽' : '—';
        $('s2FirstNote').textContent = notes[st.freq] || '';
        $('s2Monthly').textContent = p ? 'Дальше ' + fmt(s.monthly) + ' ₽ раз в месяц (' + s.f.perMonth + ' × ' + fmt(p.price) + ' ₽) с той же карты. Пауза или отмена в кабинете в любой день.' : '';
        const ready = planReady();
        $('next2').disabled = !ready;
        $('hint2').textContent = ready ? 'Дополнения на следующем шаге, всё по желанию' : 'Выберите букет или назовите бюджет';
        bar(p ? fmt(s.first) + ' ₽' : 'Какой букет', p ? 'первый платёж · дальше ' + fmt(s.monthly) + ' ₽/мес' : 'Выберите букет или бюджет', !ready);
    };

    /* Экран 3 */
    const render3 = () => {
        const p = plan(); const s = sums(); const gift = st.who === 'gift';
        $('h3').textContent = gift ? 'Что написать в открытке и добавить ли что-то к букету?' : 'Добавить что-нибудь к букету?';
        $('cardBox').hidden = !gift;
        $('textBoxes').style.gridTemplateColumns = gift ? '' : 'minmax(0, 1fr)';
        document.querySelectorAll('#addList .qz-add').forEach((b) => b.setAttribute('aria-pressed', String(st.additions.includes(Number(b.dataset.id)))));
        if (p && p.image) $('s3Photo').src = p.image;
        $('cardPreview').hidden = !(gift && st.card.trim());
        $('cardPreview').textContent = '«' + st.card.trim() + '»';
        let lines = '<div><span>' + (p ? p.name : '') + ' · ' + (FREQ_TEXT[st.freq] || '') + '</span><span>' + s.f.firstDeliveries + ' × ' + fmt(s.price) + ' ₽</span></div>';
        lines += '<div class="green"><span>Второй букет бесплатно</span><span>−' + fmt(s.price) + ' ₽</span></div>';
        adds().forEach((a) => { lines += '<div><span>' + a.name + (a.every ? ', к каждому букету' : ', один раз') + '</span><span>+' + fmt(a.every ? a.price * s.f.firstPaid : a.price) + ' ₽</span></div>'; });
        $('s3Lines').innerHTML = lines;
        $('s3First').textContent = fmt(s.first) + ' ₽';
        $('s3Monthly').textContent = 'Дальше ' + fmt(s.monthly) + ' ₽ раз в месяц';
        $('skip3').hidden = st.additions.length > 0 || !!st.card.trim();
        bar(fmt(s.first) + ' ₽', adds().length ? 'букеты ' + fmt(s.first - s.once - s.every * s.f.firstPaid) + ' ₽ + дополнения ' + fmt(s.once + s.every * s.f.firstPaid) + ' ₽' : 'без дополнений', false);
    };

    /* Экран 4 */
    const render4 = () => {
        const gift = st.who === 'gift';
        $('h4').textContent = gift ? 'Кому, куда и когда привозить?' : 'Куда и когда привозить?';
        $('recipientBox').hidden = !gift;
        const agree = gift && st.agree;
        document.querySelectorAll('#dayList button').forEach((b) => { b.setAttribute('aria-checked', String(Number(b.dataset.day) === st.day)); b.disabled = agree; });
        document.querySelectorAll('#winList button').forEach((b) => { b.setAttribute('aria-checked', String(Number(b.dataset.id) === st.winId)); b.disabled = agree; });
        const w = winLabel();
        $('whenNote').textContent = agree ? 'уточним у получателя' : ('с ' + (data.windows[0]?.from || '9:00') + ' до ' + (data.windows[data.windows.length - 1]?.to || '21:00'));
        $('whenHint').textContent = agree ? 'День и время уточним у получателя по телефону.' : 'Напомним СМС за день, курьер позвонит за час. День и время можно поменять в кабинете, любую доставку перенести или пропустить.';
        const dates = agree ? [] : deliveryDates(st.day, st.freq);
        const ready = step4Ready();
        const name = $('recName').value.trim() || 'получателю';
        if (agree) {
            $('s4Title').textContent = 'Позвоним ' + name + ', договоримся о дне и времени и привезём первый букет на этой или следующей неделе';
            $('s4Sub').textContent = 'Дальше привозим в тот же день недели и время, о которых договоримся. Их можно поменять в кабинете.';
        } else if (st.day && (st.winId || !data.windows.length)) {
            $('s4Title').textContent = 'Первый букет приедет ' + DAY_IN[st.day] + ', ' + dateText(dates[0]) + (w ? ', с ' + w.from + ' до ' + w.to : '');
            $('s4Sub').textContent = 'Дальше ' + FREQ_TEXT[st.freq] + ' ' + DAY_EACH[st.day] + ' в то же время. Напомним СМС за день, курьер позвонит за час.';
        } else {
            $('s4Title').textContent = 'Выберите день и время';
            $('s4Sub').textContent = 'Первую доставку привезём на этой или следующей неделе.';
        }
        renderCal($('cal4'), dates, null);
        $('next4').disabled = !ready;
        $('hint4').textContent = ready ? 'Остался последний шаг: проверка и оплата' : (!whenReady() ? 'Выберите день и время' : 'Укажите улицу и дом');
        bar(agree ? 'Договоримся с получателем' : (st.day && dates[0] ? 'Первый букет ' + DAY_IN[st.day] + ', ' + dateText(dates[0]) : 'Куда и когда'), agree ? 'позвоним и уточним день и время' : (w ? 'с ' + w.from + ' до ' + w.to : 'выберите день и время'), !ready);
    };
    const winLabel = () => data.windows.find((x) => x.id === st.winId) || null;

    /* Экран 5 */
    const render5 = () => {
        const p = plan(); const s = sums(); const gift = st.who === 'gift';
        const dates = st.agree ? [] : deliveryDates(st.day, st.freq);
        const w = winLabel();
        const who = gift ? ($('recName').value.trim() || 'получателю') : 'вас';
        $('r1').innerHTML = '<strong>' + p.name + '</strong>, ' + fmt(p.price) + ' ₽ за букет · ' + FREQ_TEXT[st.freq] + (st.day && !st.agree ? ', ' + DAY_EACH[st.day] : '');
        $('r2Label').textContent = gift ? 'Кому и куда' : 'Куда';
        const addr = [$('address').value.trim(), $('apartment').value.trim() && 'кв. ' + $('apartment').value.trim(), $('intercom').value.trim() && 'домофон ' + $('intercom').value.trim()].filter(Boolean).join(', ');
        $('r2').textContent = (gift ? who + ($('recPhone').value.trim() ? ', ' + $('recPhone').value.trim() : '') + ' · ' : '') + addr;
        $('r3').textContent = st.agree ? 'Договоримся с ' + who + ' по телефону' : DAY_NOM[st.day] + ', ' + dateText(dates[0]) + (w ? ', с ' + w.from + ' до ' + w.to : '') + '. Курьер позвонит за час';
        const extras = adds().map((a) => a.name.toLowerCase()).join(', ');
        $('r4').textContent = (gift ? (st.card.trim() ? '«' + st.card.trim() + '»' : 'Открытка без текста') : 'Без открытки') + (extras ? ' · ' + extras : '') + (!extras && !gift ? ', без дополнений' : '');
        if (p && p.image) $('s5Photo').src = p.image;
        let lines = '<div><span>' + s.f.firstDeliveries + ' букета в первый месяц</span><span>' + fmt(s.strike) + ' ₽</span></div>';
        lines += '<div class="green"><span>Второй букет бесплатно</span><span>−' + fmt(s.price) + ' ₽</span></div>';
        adds().forEach((a) => { lines += '<div><span>' + a.name + (a.every ? ', к каждому букету' : ', один раз') + '</span><span>+' + fmt(a.every ? a.price * s.f.firstPaid : a.price) + ' ₽</span></div>'; });
        $('s5Lines').innerHTML = lines;
        $('s5First').textContent = fmt(s.first) + ' ₽';
        let tl = '';
        if (st.agree) tl += '<div><i class="on"></i><span><b>В ближайшие дни</b> позвоним ' + who + ' и договоримся о доставке</span></div>';
        else {
            tl += '<div><i class="on"></i><span><b>' + dateText(dates[0]) + '</b> первая доставка</span></div>';
            if (dates[1]) tl += '<div><i class="gift"></i><span><b>' + dateText(dates[1]) + '</b> второй букет, бесплатно</span></div>';
        }
        tl += '<div><i></i><span><b>' + dateText(nextCharge()) + '</b> списание ' + fmt(s.monthly) + ' ₽ за следующие ' + s.f.perMonth + ' ' + (s.f.perMonth === 1 ? 'букет' : 'букета') + ', напомним за 3 дня</span></div>';
        $('s5Timeline').innerHTML = tl;
        $('s5Personal').textContent = st.agree ? 'Букет «' + p.name + '» будет у ' + who + ' в день, о котором договоримся.' : DAY_IN[st.day].charAt(0).toUpperCase() + DAY_IN[st.day].slice(1) + ', ' + dateText(dates[0]) + (w ? ', с ' + w.from + ' до ' + w.to : '') + ' букет «' + p.name + '» будет у ' + who + '.';
        $('payBtn').textContent = 'Оплатить ' + fmt(s.first) + ' ₽';
        $('legalMonthly').textContent = fmt(s.monthly) + ' ₽';
        bar(fmt(s.first) + ' ₽', 'первый платёж · дальше ' + fmt(s.monthly) + ' ₽/мес', false, 'Оплатить');
    };

    const renders = { 1: render1, 2: render2, 3: render3, 4: render4, 5: render5 };
    const render = () => { if (renders[st.step]) renders[st.step](); };

    /* Нижняя панель на телефоне */
    const bar = (main, sub, disabled, label) => {
        $('barMain').textContent = main; $('barSub').textContent = sub;
        $('barNext').disabled = !!disabled; $('barNext').textContent = label || 'Дальше';
    };

    /* Переходы */
    const go = (step, opts = {}) => {
        const from = st.step;
        st.step = step;
        [0, 1, 2, 3, 4, 5].forEach((n) => { $('qz' + n).hidden = n !== step; });
        $('qzBack').hidden = step === 0; $('qzLogin').hidden = step !== 0;
        $('qzSteps').hidden = step === 0; $('qzBar').hidden = step === 0;
        if (step > 0) {
            $('qzStepLabel').textContent = 'Шаг ' + step + ' из 5';
            $('qzBarFill').style.width = (step * 20) + '%';
            $('qzSteps').querySelector('[role="progressbar"]').setAttribute('aria-valuenow', step);
        }
        render();
        const sec = $('qz' + step);
        if (!reduced) { sec.style.animation = 'none'; void sec.offsetWidth; sec.style.animation = 'rise .25s var(--ease-enter) both'; }
        window.scrollTo({ top: 0, behavior: 'auto' });
        sec.querySelector('h1')?.focus({ preventScroll: true });
        if (step > 0 && step > from && !opts.silent) track('quiz_step', { step, plan_id: st.planId || undefined, frequency: st.freq || undefined, mode: st.who || undefined });
        if (step === 2 && from === 1) ecommerce('impressions', data.plans.map((p, i) => ({ id: String(p.id), name: p.name, category: 'Подписка на цветы', price: p.price, list: 'Квиз / букеты', position: i + 1 })));
        if (step === 3 && from === 2 && plan()) { const p = plan(); ecommerce('add', { products: [{ id: String(p.id || 'custom'), name: p.name, category: 'Подписка на цветы', price: p.price, quantity: sums().f.firstPaid, variant: sums().f.label }] }); }
    };
    const next = () => {
        if (st.step === 1 && st.who && st.freq) go(2);
        else if (st.step === 2 && planReady()) go(3);
        else if (st.step === 3) go(4);
        else if (st.step === 4 && validate4()) go(5);
        else if (st.step === 5) pay();
    };
    const validate4 = () => {
        let ok = true;
        setErr('errAddress', $('address'), $('address').value.trim().length < 5 ? 'Укажите улицу и дом.' : '');
        if ($('address').value.trim().length < 5) ok = false;
        if (st.who === 'gift' && $('recPhone').value.trim() && !phoneOk($('recPhone').value)) { setErr('errRecPhone', $('recPhone'), 'Телефон в формате +7 924 000-00-00.'); ok = false; } else setErr('errRecPhone', $('recPhone'), '');
        if (!whenReady()) ok = false;
        if (!ok) document.querySelector('#qz4 [aria-invalid="true"]')?.focus();
        return ok;
    };
    const setErr = (id, input, msg) => { const e = $(id); if (e) { e.hidden = !msg; e.textContent = msg || ''; } if (input) input.setAttribute('aria-invalid', msg ? 'true' : 'false'); };

    /* Оплата: создаём подписку без входа и уходим в ЮKassa */
    let paying = false;
    const pay = async () => {
        if (paying) return;
        const payError = (msg) => { $('payError').hidden = !msg; $('payError').textContent = msg || ''; };
        payError('');
        let ok = true;
        if ($('senderName').value.trim().length < 2) { setErr('errName', $('senderName'), 'Как к вам обращаться?'); ok = false; } else setErr('errName', $('senderName'), '');
        if (!phoneOk($('senderPhone').value)) { setErr('errPhone', $('senderPhone'), 'В номере не хватает цифр. Пример: +7 924 000-00-00'); ok = false; } else setErr('errPhone', $('senderPhone'), '');
        if (!ok) { document.querySelector('#qz5 [aria-invalid="true"]')?.focus(); return; }
        paying = true; $('payBtn').setAttribute('aria-busy', 'true'); $('barNext').setAttribute('aria-busy', 'true');
        const gift = st.who === 'gift';
        const body = {
            who: st.who, frequency: st.freq,
            plan_id: st.planId > 0 ? st.planId : null, budget: st.planId === 0 ? parseInt(String(st.own).replace(/\D/g, ''), 10) : null,
            option_ids: st.additions, card_text: gift ? st.card.trim() || null : null, wishes: $('wishes').value.trim() || null,
            receiving_name: gift ? $('recName').value.trim() || null : null, receiving_phone: gift ? $('recPhone').value.trim() || null : null,
            hide_sender: gift && $('hideSender').checked, agree_with_recipient: gift && st.agree,
            address: $('address').value.trim(), apartment: $('apartment').value.trim() || null, entrance: $('entrance').value.trim() || null, intercom: $('intercom').value.trim() || null,
            comment: $('comment').value.trim() || null, delivery_day: st.day || 5, time_delivery_id: st.winId,
            sender_name: $('senderName').value.trim(), sender_phone: $('senderPhone').value.trim(),
        };
        try {
            const r = await postJson(data.urls.store, body);
            if (!r.ok) {
                if (r.errors && r.errors.sender_phone) setErr('errPhone', $('senderPhone'), r.errors.sender_phone[0]);
                payError(r.message || firstError(r) || 'Не удалось оформить. Проверьте поля.');
                return;
            }
            track('payment_redirect', { plan_id: st.planId || undefined, frequency: st.freq, mode: st.who });
            window.location.assign(r.payment_url);
        } catch (e) {
            payError('Нет связи с сервером. Попробуйте ещё раз.');
        } finally {
            paying = false; $('payBtn').removeAttribute('aria-busy'); $('barNext').removeAttribute('aria-busy');
        }
    };

    /* Обработчики */
    $('qzStart').addEventListener('click', () => { track('quiz_start', { source: 'home' }); go(1); });
    $('qzBack').addEventListener('click', (e) => { e.preventDefault(); go(Math.max(0, st.step - 1), { silent: true }); });
    document.querySelectorAll('#whoList .qz-who-i').forEach((b) => b.addEventListener('click', () => { st.who = b.dataset.who; b.parentElement.querySelectorAll('[role="radio"]').forEach((x) => x.setAttribute('aria-checked', String(x === b))); render(); }));
    document.querySelectorAll('#freqList .qz-freq-i').forEach((b) => b.addEventListener('click', () => { st.freq = b.dataset.freq; b.parentElement.querySelectorAll('[role="radio"]').forEach((x) => x.setAttribute('aria-checked', String(x === b))); render(); }));
    document.querySelectorAll('#planList .qz-plan').forEach((b) => b.addEventListener('click', () => { st.planId = Number(b.dataset.id); render(); }));
    $('ownBudget').addEventListener('input', (e) => { st.own = e.target.value; if (e.target.value.trim()) st.planId = 0; render(); });
    $('ownBox').addEventListener('click', (e) => { if (e.target.tagName !== 'INPUT' && !$('ownBudget').value.trim()) $('ownBudget').focus(); });
    document.querySelectorAll('#addList .qz-add').forEach((b) => b.addEventListener('click', () => { const id = Number(b.dataset.id); st.additions = st.additions.includes(id) ? st.additions.filter((x) => x !== id) : st.additions.concat([id]); render(); }));
    $('cardText').addEventListener('input', (e) => { st.card = e.target.value; render(); });
    document.querySelectorAll('#cardPills button').forEach((b) => b.addEventListener('click', () => { st.card = b.textContent; $('cardText').value = st.card; render(); }));
    document.querySelectorAll('#dayList button').forEach((b) => b.addEventListener('click', () => { st.day = Number(b.dataset.day); render(); }));
    document.querySelectorAll('#winList button').forEach((b) => b.addEventListener('click', () => { st.winId = Number(b.dataset.id); render(); }));
    $('agreeRec').addEventListener('change', (e) => { st.agree = e.target.checked; render(); });
    ['address', 'recName', 'recPhone'].forEach((id) => $(id).addEventListener('input', () => render()));
    $('next1').addEventListener('click', next); $('next2').addEventListener('click', next); $('next3').addEventListener('click', next); $('next4').addEventListener('click', next);
    $('skip3').addEventListener('click', () => { st.additions = []; go(4); });
    $('payBtn').addEventListener('click', pay);
    $('barNext').addEventListener('click', next);
    document.querySelectorAll('.qz-edit').forEach((b) => b.addEventListener('click', () => go(Number(b.dataset.go), { silent: true })));
    document.addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.tagName === 'INPUT' && st.step > 0 && st.step < 5) { e.preventDefault(); next(); } });

    // Прямая ссылка на шаг: /?step=1 открывает первый вопрос, иначе главная
    const wanted = Number(new URLSearchParams(location.search).get('step'));
    go(wanted === 1 ? 1 : 0, { silent: true });
}
