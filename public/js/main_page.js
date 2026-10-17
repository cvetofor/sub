document.addEventListener('DOMContentLoaded', function () {
    const setActive = (activeBtn, ...otherBtns) => {
        // Сначала снимаем "active" у всех
        otherBtns.forEach(btn => {
            if (btn) btn.classList.remove('active');
        });

        // Потом выделяем активную кнопку
        if (activeBtn) activeBtn.classList.add('active');
    };

    // ---- СИНХРОНИЗАЦИЯ КНОПОК ЧАСТОТЫ ----
    const deliveryCounts = {
        "weekly": 4,
        "biweekly": 2,
        "monthly": 1
    };

    // «1 доставка», «2 доставки», «5 доставок»
    const deliveriesWord = (n) => {
        const mod10 = n % 10, mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return 'доставка';
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'доставки';
        return 'доставок';
    };

    const selectPlan = (plan, plans) => {
        plans.forEach(p => {
            const btn = p.querySelector(".choosePlanBtnPSection");
            const label = p.querySelector("p.text-rose-600");
            const customBtn = p.querySelector(".plan-btn");

            p.classList.remove("border-rose-500", "shadow-rose-500", "shadow-lg", 'active-plan', "shadow-xl/30",);
            p.classList.add("border-rose-200");

            if (btn) {
                btn.textContent = "Выбрать план";
                btn.className = "choosePlanBtnPSection mt-4 w-full px-4 py-2 rounded-2xl bg-white border border-rose-300 cursor-pointer";
            }
            if (label) label.style.display = "none";
            if (customBtn) customBtn.style.display = "none";
        });

        const button = plan.querySelector(".choosePlanBtnPSection");
        const selectedLabel = plan.querySelector("p.text-rose-600");
        const customBtn = plan.querySelector(".plan-btn");

        plan.classList.add("border-rose-500", "shadow-rose-500", "shadow-xl/30", 'active-plan',);
        plan.classList.remove("border-rose-200");

        if (button) {
            button.textContent = "Оформить подписку";
            button.className = "choosePlanBtnPSection mt-4 w-full px-4 py-2 rounded-2xl bg-rose-600 text-white cursor-pointer";
        }
        if (selectedLabel) selectedLabel.style.display = "inline";
        if (customBtn) customBtn.style.display = "block";
    };


    const updatePlan = (card, deliveriesPerMonth) => {
        const deliveryText = card.querySelector(".frequency_per_month");
        if (deliveryText) {
            deliveryText.textContent = `≈ ${deliveriesPerMonth} ${deliveriesWord(deliveriesPerMonth)} в месяц`;
        }

        let optionsSum = 0;
        const optionsParagraph = card.querySelector("p.mt-3.text-sm.text-gray-600");
        if (optionsParagraph) {
            optionsParagraph.querySelectorAll("span").forEach(span => {
                const match = span.textContent.match(/\+(\d+)/);
                if (match) optionsSum += parseInt(match[1]);
            });
        }

        const priceDiv = card.querySelector("div.text-3xl.font-bold");
        let pricePerDelivery = 0;
        if (priceDiv) {
            const match = priceDiv.textContent.match(/(\d+)/);
            if (match) pricePerDelivery = parseInt(match[1]);
        }

        const totalParagraph = card.querySelector(".total_ready_plan");
        if (totalParagraph) {
            totalParagraph.textContent = `${(pricePerDelivery + optionsSum) * deliveriesPerMonth} ₽`;
        }
    };

    const allSelects = document.querySelectorAll(".frequency");
    allSelects.forEach(select => {
        select.addEventListener("change", function () {
            const card = this.closest(".swiper-slide");
            const value = this.value;
            const count = deliveryCounts[value] || 1;
            updatePlan(card, count);
        });

        // при загрузке сразу обновляем
        const value = select.value;
        const card = select.closest(".swiper-slide");
        const count = deliveryCounts[value] || 1;
        updatePlan(card, count);
    });

    // Переключение "Готовые планы / Собрать самому"
    const readyBtn = document.getElementById('readyBtn');
    const customBtns = document.querySelectorAll('.customBtn');
    const readyPlansWrapper = document.getElementById('readyPlansWrapper'); // блок с готовыми планами
    const customWrapper = document.getElementById('customWrapper'); // блок "Собрать самому"

    if (readyBtn && customBtns.length && readyPlansWrapper && customWrapper) {
        // Клик по "Готовые планы"
        readyBtn.addEventListener('click', () => {
            setActive(readyBtn, ...customBtns);
            readyPlansWrapper.classList.remove('hidden');
            customWrapper.classList.add('hidden');
        });

        // Клик по любой кнопке "Собрать самому"
        customBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                setActive(btn, readyBtn, ...customBtns);
                customWrapper.classList.remove('hidden');
                readyPlansWrapper.classList.add('hidden');
            });
        });
    }

    // ---- ЛОГИКА ДЛЯ ПЛАНОВ ----
    const plansContainer = document.querySelector(".swiper-wrapper");
    if (plansContainer) {
        const plans = Array.from(plansContainer.querySelectorAll(".swiper-slide"));

        plans.forEach((plan, index) => {
            plan.addEventListener("click", () => selectPlan(plan, plans));
            if (index === 0) selectPlan(plan, plans);
        });
    }

    // Скролл к планам
    const plansSection = document.getElementById('plansSection');
    const choosePlanBtn = document.getElementById('choosePlanBtn');
    if (choosePlanBtn) {
        choosePlanBtn.addEventListener('click', () => {
            readyPlansWrapper.classList.remove('hidden');
            customWrapper.classList.add('hidden');
            plansSection.scrollIntoView({ behavior: 'smooth' });
        });
    }

    const chooseCustomPlanBtn = document.getElementById('chooseCustomPlanBtn');
    if (chooseCustomPlanBtn) {
        chooseCustomPlanBtn.addEventListener('click', () => {
            customWrapper.classList.remove('hidden');
            readyPlansWrapper.classList.add('hidden');
            plansSection.scrollIntoView({ behavior: 'smooth' });
        });
    }

    const rangeInput = document.getElementById('budgetRange');

    rangeInput.addEventListener('input', () => {
        const value = ((rangeInput.value - rangeInput.min) / (rangeInput.max - rangeInput.min)) * 100;
        rangeInput.style.background = `linear-gradient(to right, #f43f5e 0%, #f43f5e ${value}%, #fca5a5 ${value}%, #fca5a5 100%)`;
    });

    const budgetValueMain = document.getElementById('budgetValueMain');
    const budgetValueAside = document.getElementById('budgetValueAside');
    const frequencyOutput = document.getElementById('frequencyOutputAside');

    // Функция для форматирования числа с пробелами
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    // Функция для подсчета общей суммы с учетом опций
    function calculateTotalAmount() {
    const baseBudget = parseInt(rangeInput.value) || parseInt(rangeInput.min) || 1000;
    let totalOptionsPrice = 0;

    const deliveryOptions = document.querySelectorAll('input[type="checkbox"]');
    deliveryOptions.forEach(checkbox => {
        if (checkbox.checked) {
            const optionPrice = parseInt(checkbox.dataset.optionPrice) || 0;
            const isEveryDelivery = checkbox.dataset.optionIsEveryDelivery === "1" || checkbox.dataset.optionIsEveryDelivery === "true";

            if (isEveryDelivery) {
                // Если опция применяется каждый раз, прибавляем цену за каждую доставку
                const frequency = deliveryCounts[frequencyOutput.dataset.frequency] || 1;
                totalOptionsPrice += optionPrice * frequency;
            } else {
                // Если опция только один раз, добавляем один раз
                totalOptionsPrice += optionPrice;
            }
        }
    });

    let frequency = deliveryCounts[frequencyOutput.dataset.frequency] || 1;

    return baseBudget * frequency + totalOptionsPrice;
}

    // Функция для обновления отображения общей суммы
    function updateTotalAmount() {
        const totalAmount = calculateTotalAmount();
        const totalAmountElement = document.getElementById('totalAmountElement');
        const mobileTotal = document.getElementById('mobileTotal');

        if (totalAmountElement) {
            totalAmountElement.textContent = formatNumber(totalAmount) + ' ₽';
            totalAmountElement.dataset.total = totalAmount;
        }
        if (mobileTotal) {
            mobileTotal.textContent = formatNumber(totalAmount) + ' ₽';
        }
    }

    rangeInput.addEventListener('input', () => {
        budgetValueMain.textContent = formatNumber(rangeInput.value) + ' ₽';
        budgetValueAside.textContent = formatNumber(rangeInput.value) + ' ₽';
        budgetValueAside.dataset.totalDelivery = rangeInput.value;
        updateTotalAmount(); // Обновляем общую сумму при изменении бюджета
    });

    const citySelect = document.getElementById('citySelect');
    const cityOutput = document.getElementById('cityOutput');

    // При загрузке сразу подставим значение по умолчанию
    cityOutput.textContent = citySelect.options[citySelect.selectedIndex].text;

    // При смене города обновляем <b>
    citySelect.addEventListener('change', function () {
        cityOutput.textContent = this.options[this.selectedIndex].text;
    });

    const freqButtons = document.querySelectorAll('.toggle-btn');

    // при загрузке выводим активную кнопку
    let activeBtn = document.querySelector('.toggle-btn.active');
    if (activeBtn) {
        frequencyOutput.textContent = activeBtn.textContent.trim();
    }

    // обработчик клика по кнопкам
    freqButtons.forEach(btn => {
    btn.addEventListener('click', function () {
        // снять active у всех
        freqButtons.forEach(b => {
            b.classList.remove('active', 'bg-rose-100', 'border-rose-400', 'text-rose-700');
            b.classList.add('bg-white', 'border-rose-200');
        });

        // выделить текущую
        this.classList.add('active', 'bg-rose-100', 'border-rose-400', 'text-rose-700');
        this.classList.remove('bg-white', 'border-rose-200');

        // дублируем в output и data, и текст
        frequencyOutputAside.dataset.frequency = this.dataset.frequency;
        frequencyOutputAside.textContent = this.textContent.trim();

        updateTotalAmount();
    });
});

    // ---- ЛОГИКА ДЛЯ ЧЕКБОКСОВ ОПЦИЙ ДОСТАВКИ ----
    const deliveryOptions = document.querySelectorAll('input[type="checkbox"]');
    const optionsList = document.querySelector('.lg\\:col-span-5 .text-sm.mt-1.space-y-1');

    if (optionsList) {
        // Получаем все существующие опции из списка
        const existingOptions = optionsList.querySelectorAll('li');
        if (existingOptions.length > 0) {
            // Добавляем data-атрибуты к существующим элементам, если их нет
            existingOptions.forEach(li => {
                if (!li.dataset.optionName) {
                    const text = li.textContent;
                    const name = text.split(' +')[0];
                    const price = text.match(/\+(\d+)₽/)?.[1] || '0';
                    li.dataset.optionName = name;
                    li.dataset.optionPrice = price;
                }
            });
        }
    }

    // Обработчик для всех чекбоксов
    deliveryOptions.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateDeliveryOptions();
            updateTotalAmount(); // Обновляем общую сумму при изменении опций
        });
    });

    function updateDeliveryOptions() {
        const optionsList = document.querySelector('.lg\\:col-span-5 .text-sm.mt-1.space-y-1');
        if (!optionsList) return;

        const listItems = optionsList.querySelectorAll('li');

        listItems.forEach(li => {
            const optionName = li.dataset.optionName;
            const optionPrice = li.dataset.optionPrice || '0';

            if (!optionName) return;

            // Ищем соответствующий чекбокс
            const checkbox = Array.from(deliveryOptions).find(cb => {
                const label = cb.closest('label');
                return label && label.textContent.includes(optionName);
            });

            if (checkbox && checkbox.checked) {
                // Опция выбрана
                li.className = 'opacity-100';
                li.innerHTML = `${optionName} +${optionPrice}₽`;
            } else {
                // Опция не выбрана
                li.className = 'hidden';
                li.innerHTML = `${optionName} (не выбрано)`;
            }
        });
    }

    // Разлочить кнопки "Оформить подписку" (в боковой панели и в нижней панели на телефоне)
    const inputDeliveryAddress = document.getElementById('deliveryAddress');
    const asideSubscribeBtn = document.getElementById('asideSubscribeBtn');
    const mobileSubscribeBtn = document.getElementById('mobileSubscribeBtn');
    const subscribeButtons = [asideSubscribeBtn, mobileSubscribeBtn].filter(Boolean);
    const subscribeHints = document.querySelectorAll('.subscribe-hint');

    const setSubscribeEnabled = (enabled) => {
        subscribeButtons.forEach(btn => {
            btn.disabled = !enabled;
            btn.classList.toggle('cursor-not-allowed', !enabled);
            btn.classList.toggle('opacity-60', !enabled);
            btn.classList.toggle('cursor-pointer', enabled);
        });
        subscribeHints.forEach(h => h.classList.toggle('hidden', enabled));
    };

    inputDeliveryAddress.addEventListener('input', () => {
        setSubscribeEnabled(inputDeliveryAddress.value.trim() !== '');
    });
    setSubscribeEnabled(inputDeliveryAddress.value.trim() !== '');

    // Открыть/закрыть модалку с оформлением подписки
    const subscribeModal = document.getElementById('subscription-modal');
    const closeBtn = document.getElementById('closeBtn');
    const openSubscribeModal = () => subscribeModal.classList.remove('hidden');
    const closeSubscribeModal = () => subscribeModal.classList.add('hidden');

    closeBtn.addEventListener('click', closeSubscribeModal);
    subscribeModal.addEventListener('click', (e) => {
        if (e.target === subscribeModal) closeSubscribeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSubscribeModal();
    });

    subscribeButtons.forEach(btn => btn.addEventListener('click', openSubscribeModal));

    document.querySelectorAll('.choosePlanBtnPSection').forEach(element => {
        element.addEventListener('click', openSubscribeModal);
    });

    updateDeliveryOptions();
    updateTotalAmount();
});
