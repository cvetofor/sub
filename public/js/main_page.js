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

    const readyBtn = document.getElementById('readyBtn');
    const customBtn = document.getElementById('customBtn');
    if (readyBtn && customBtn) {
        readyBtn.addEventListener('click', () => setActive(readyBtn, customBtn));
        customBtn.addEventListener('click', () => setActive(customBtn, readyBtn));
    }

    document.querySelectorAll('.btn-group').forEach(group => {
        const buttons = group.querySelectorAll('.toggle-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    });

    const plansContainer = document.querySelector(".swiper-wrapper");
    if (!plansContainer) return;

    const plans = Array.from(plansContainer.querySelectorAll(".swiper-slide"));

    plans.forEach((plan, index) => {
        plan.addEventListener("click", () => selectPlan(plan, plans));
        if (index === 0) selectPlan(plan, plans);
    });

    const deliveryCounts = {
        "Еженедельно": 4,
        "Раз в 2 недели": 2,
        "Раз в месяц": 1
    };

    const frequencyButtons = document.querySelectorAll(".toggle-btn");
    const planCards = plans;

    frequencyButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            frequencyButtons.forEach(b => {
                b.classList.remove("bg-rose-100", "border-rose-400", "text-rose-700", "active");
                b.classList.add("bg-white", "border-rose-200", "text-gray-600");
            });

            btn.classList.add("bg-rose-100", "border-rose-400", "text-rose-700", "active");
            btn.classList.remove("bg-white", "border-rose-200", "text-gray-600");

            const count = deliveryCounts[btn.textContent] || 1;
            planCards.forEach(card => updatePlan(card, count));
        });
    });

    const activeBtn = document.querySelector(".toggle-btn.active");
    if (activeBtn) {
        const count = deliveryCounts[activeBtn.textContent] || 1;
        planCards.forEach(card => updatePlan(card, count));
    }

    const plansSection = document.getElementById('plansSection');

    document.getElementById('subscribeBtn').addEventListener('click', function () {
        plansSection.scrollIntoView({ behavior: 'smooth' });
    });

    document.getElementById('choosePlanBtn').addEventListener('click', function () {
        plansSection.scrollIntoView({ behavior: 'smooth' });
    });
});
