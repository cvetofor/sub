// Поля ввода: маска телефона и подсказки адреса по городу доставки (DaData).

/* Маска телефона: «+7 (9XX) XXX-XX-XX». При фокусе в пустом поле подставляется «+7 », дальше человек вводит
   десять цифр. Вставка «8 912…», «7912…» и «912…» приводится к одному виду. Поле остаётся обычным текстом,
   сервер нормализует номер сам. */
export function attachPhoneMask(input) {
    if (!input || input.dataset.masked) return;
    input.dataset.masked = '1';
    input.setAttribute('inputmode', 'tel');
    input.setAttribute('maxlength', '18');
    const format = (raw) => {
        let d = String(raw).replace(/\D/g, '');
        if (d.startsWith('8') || d.startsWith('7')) d = d.slice(1);
        d = d.slice(0, 10);
        let out = '+7';
        if (d.length) out += ' (' + d.slice(0, 3);
        if (d.length >= 3) out += ')';
        if (d.length > 3) out += ' ' + d.slice(3, 6);
        if (d.length > 6) out += '-' + d.slice(6, 8);
        if (d.length > 8) out += '-' + d.slice(8, 10);
        return out;
    };
    const apply = () => {
        const digits = input.value.replace(/\D/g, '');
        input.value = digits ? format(input.value) : '';
        try { input.setSelectionRange(input.value.length, input.value.length); } catch (e) { /* не текстовое поле */ }
    };
    input.addEventListener('focus', () => { if (!input.value.trim()) { input.value = '+7 '; } });
    input.addEventListener('blur', () => { if (input.value.replace(/\D/g, '') === '7' || input.value.trim() === '+7') input.value = ''; });
    input.addEventListener('input', (e) => {
        // Удаление: не мешаем стирать разделители, переформатируем по цифрам
        if (e.inputType === 'deleteContentBackward' && /\D$/.test(input.value)) { input.value = input.value.replace(/\D+$/, ''); }
        apply();
    });
    input.addEventListener('paste', () => setTimeout(apply, 0));
    if (input.value.trim()) apply();
}

/* Подсказки адреса DaData, ограниченные городом доставки. Нужен window.DADATA_TOKEN (ключ API подсказок)
   и window.DADATA_CITY. Без ключа поле работает как обычное. На выбор подсказки в поле подставляется
   «улица, дом», в data-fias кладётся код дома: сервер по нему проверит, что адрес в городе. */
export function attachAddressSuggest(input) {
    const token = window.DADATA_TOKEN, city = window.DADATA_CITY;
    if (!input || !token || !city || input.dataset.suggest) return;
    input.dataset.suggest = '1';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    const wrap = input.parentElement;
    if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';
    const list = document.createElement('div');
    list.className = 'sugg';
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    input.insertAdjacentElement('afterend', list);
    let items = [], active = -1, timer = null, controller = null, chosen = input.value;
    const close = () => { list.hidden = true; list.innerHTML = ''; items = []; active = -1; input.setAttribute('aria-expanded', 'false'); };
    const pick = (i) => {
        const s = items[i]; if (!s) return;
        input.value = s.label; input.dataset.fias = s.fias || ''; input.dataset.city = s.city || ''; chosen = input.value;
        close(); input.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const render = () => {
        list.innerHTML = '';
        items.forEach((s, i) => {
            const b = document.createElement('button');
            b.type = 'button'; b.setAttribute('role', 'option'); b.textContent = s.label; b.setAttribute('aria-selected', String(i === active));
            b.addEventListener('mousedown', (e) => { e.preventDefault(); pick(i); });
            list.appendChild(b);
        });
        list.hidden = !items.length; input.setAttribute('aria-expanded', String(!!items.length));
    };
    const query = async () => {
        const q = input.value.trim();
        if (q.length < 3) { close(); return; }
        controller?.abort(); controller = new AbortController();
        try {
            const r = await fetch('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', {
                method: 'POST', signal: controller.signal,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Token ' + token },
                body: JSON.stringify({ query: q, count: 7, locations: [{ city }], restrict_value: true, from_bound: { value: 'street' }, to_bound: { value: 'house' } }),
            });
            const json = await r.json();
            items = (json.suggestions || []).map((s) => ({
                label: s.value,
                fias: s.data.house_fias_id || s.data.street_fias_id || s.data.fias_id || '',
                city: s.data.city || s.data.settlement || '',
                house: s.data.house || '',
            }));
            active = -1; render();
        } catch (e) { /* подсказки не должны мешать вводу */ }
    };
    input.addEventListener('input', () => {
        if (input.value !== chosen) { input.dataset.fias = ''; input.dataset.city = ''; }
        clearTimeout(timer); timer = setTimeout(query, 250);
    });
    input.addEventListener('keydown', (e) => {
        if (list.hidden) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(items.length - 1, active + 1); render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); render(); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); pick(active); }
        else if (e.key === 'Escape') { close(); }
    });
    input.addEventListener('blur', () => setTimeout(close, 150));
}

export function initFields(root = document) {
    root.querySelectorAll('input[type="tel"]').forEach(attachPhoneMask);
    root.querySelectorAll('input[data-suggest-address]').forEach(attachAddressSuggest);
}
