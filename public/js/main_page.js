document.addEventListener('DOMContentLoaded', function () {
    const setActive = (activeBtn, inactiveBtn) => {
        activeBtn.classList.add('active');
        inactiveBtn.classList.remove('active');
    };

    const selectPlan = (plan, plans) => {
        plans.forEach(p => {
            const btn = p.querySelector("button");
            const label = p.querySelector("p.text-rose-600");

            p.classList.remove("border-rose-500", "shadow-rose-100", "shadow-lg");
            p.classList.add("border-rose-200");

            if (btn) {
                btn.textContent = "Выбрать план";
                btn.className = "mt-4 w-full px-4 py-2 rounded-2xl bg-white border border-rose-300 cursor-pointer";
            }
            if (label) label.style.display = "none";
        });

        const button = plan.querySelector("button");
        const selectedLabel = plan.querySelector("p.text-rose-600");

        plan.classList.add("border-rose-500", "shadow-rose-100", "shadow-lg");
        plan.classList.remove("border-rose-200");

        if (button) {
            button.textContent = "План выбран";
            button.className = "mt-4 w-full px-4 py-2 rounded-2xl bg-rose-600 text-white cursor-pointer";
        }
        if (selectedLabel) selectedLabel.style.display = "inline";
    };

    const updatePlan = (card, deliveriesPerMonth) => {
        const deliveryText = card.querySelector("p.mt-1.text-sm.text-gray-600");
        if (deliveryText) deliveryText.textContent = `≈ ${deliveriesPerMonth} доставк(и) в месяц`;

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

        const totalParagraph = card.querySelector("div.mt-4.p-3.rounded-2xl.bg-rose-100.border p.text-2xl.font-extrabold");
        if (totalParagraph) totalParagraph.textContent = `${pricePerDelivery * deliveriesPerMonth + optionsSum} ₽`;
    };

    // Переключение "Готовые планы / Собрать самому"
    const readyBtn = document.getElementById('readyBtn');
    const customBtn = document.getElementById('customBtn');
    if (readyBtn && customBtn) {
        readyBtn.addEventListener('click', () => setActive(readyBtn, customBtn));
        customBtn.addEventListener('click', () => setActive(customBtn, readyBtn));
    }

    // ---- СИНХРОНИЗАЦИЯ КНОПОК ЧАСТОТЫ ----
    const deliveryCounts = {
        "Еженедельно": 4,
        "Раз в 2 недели": 2,
        "Раз в месяц": 1
    };

    const allFrequencyButtons = document.querySelectorAll(".toggle-btn");

    allFrequencyButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const value = btn.textContent.trim();

            // снять active у всех
            allFrequencyButtons.forEach(b => {
                b.classList.remove("bg-rose-100", "border-rose-400", "text-rose-700", "active");
                b.classList.add("bg-white", "border-rose-200", "text-gray-600");
            });

            // активировать во всех группах ту же частоту
            allFrequencyButtons.forEach(b => {
                if (b.textContent.trim() === value) {
                    b.classList.add("bg-rose-100", "border-rose-400", "text-rose-700", "active");
                    b.classList.remove("bg-white", "border-rose-200", "text-gray-600");
                }
            });

            // обновляем планы
            const count = deliveryCounts[value] || 1;
            const plansContainer = document.querySelector(".swiper-wrapper");
            if (plansContainer) {
                const plans = Array.from(plansContainer.querySelectorAll(".swiper-slide"));
                plans.forEach(card => updatePlan(card, count));
            }
        });
    });

    // ---- ЛОГИКА ДЛЯ ПЛАНОВ ----
    const plansContainer = document.querySelector(".swiper-wrapper");
    if (plansContainer) {
        const plans = Array.from(plansContainer.querySelectorAll(".swiper-slide"));

        plans.forEach((plan, index) => {
            plan.addEventListener("click", () => selectPlan(plan, plans));
            if (index === 0) selectPlan(plan, plans);
        });

        // при загрузке активная частота
        const activeBtn = document.querySelector(".toggle-btn.active");
        if (activeBtn) {
            const count = deliveryCounts[activeBtn.textContent.trim()] || 1;
            plans.forEach(card => updatePlan(card, count));
        }
    }

    // Скролл к планам
    const plansSection = document.getElementById('plansSection');
    const choosePlanBtn = document.getElementById('choosePlanBtn');
    if (choosePlanBtn) {
        choosePlanBtn.addEventListener('click', () => {
            plansSection.scrollIntoView({ behavior: 'smooth' });
        });
    }

    const subscribeBtn = document.getElementById('subscribeBtn');
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', () => {
            plansSection.scrollIntoView({ behavior: 'smooth' });
        });
    }

    const rangeInput = document.getElementById('budgetRange');
    const budgetValueMain = document.getElementById('budgetValueMain');
    const budgetValueAside = document.getElementById('budgetValueAside');

    // Функция для форматирования числа с пробелами
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    rangeInput.addEventListener('input', () => {
        budgetValueMain.textContent = formatNumber(rangeInput.value) + ' ₽';
        budgetValueAside.textContent = formatNumber(rangeInput.value) + ' ₽';
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
    const frequencyOutput = document.getElementById('frequencyOutput');

    // при загрузке выводим активную кнопку
    let activeBtn = document.querySelector('.toggle-btn.active');
    if (activeBtn) {
        frequencyOutput.textContent = activeBtn.textContent.trim();
    }

    // обработчик клика по кнопкам
    freqButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            // выделяем текущую
            this.classList.add('active', 'bg-rose-100', 'border-rose-400', 'text-rose-700');
            this.classList.remove('bg-white', 'border-rose-200');

            // записываем текст
            frequencyOutput.textContent = this.textContent.trim();
        });
    });
});
