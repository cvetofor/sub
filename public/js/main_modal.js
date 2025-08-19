const sameCustomerCheckbox = document.getElementById('same_customer');
const recipientFio = document.getElementById('recipientFio');
const recipientPhone = document.getElementById('recipientPhone');

sameCustomerCheckbox.addEventListener('change', (e) => {
    if (e.target.checked) {
        [recipientFio, recipientPhone].forEach(el => {
            el.classList.add('max-h-0', 'opacity-0', 'overflow-hidden');
            el.classList.remove('max-h-40', 'opacity-100');
        });
    } else {
        [recipientFio, recipientPhone].forEach(el => {
            el.classList.remove('max-h-0', 'opacity-0');
            el.classList.add('max-h-40', 'opacity-100');
        });
    }
});
 
const payBtn = document.getElementById('payBtn'); 

function showError(input, message) {
    // Удаляем старое сообщение
    let oldError = input.parentElement.querySelector(".error-text");
    if (oldError) oldError.remove();

    if (message) {
        // Создаем новый элемент ошибки под инпутом
        let errorEl = document.createElement("p");
        errorEl.className = "error-text text-xs text-red-500 mt-1";
        errorEl.innerText = message;
        input.parentElement.appendChild(errorEl);
        input.classList.add("border-red-400");
    } else {
        input.classList.remove("border-red-400");
    }
}

function validatePhone(phone) {
    const phonePattern = /^\+7\s?\(\d{3}\)\s?\d{3}-\d{2}-\d{2}$/;
    return phonePattern.test(phone.trim());
}

function validateForm() {
    let valid = true;

    const customerFio = document.querySelector('input[placeholder="ФИО"]');
    const customerPhone = document.querySelectorAll('#inputPhone')[0];
    const recipientFio = document.querySelector('#recipientFio input');
    const recipientPhone = document.querySelectorAll('#inputPhone')[1];

    // проверка заказчика
    if (customerFio.value.trim().length < 3) {
        showError(customerFio, "Введите корректное ФИО");
        valid = false;
    } else showError(customerFio, "");

    if (!validatePhone(customerPhone.value)) {
        showError(customerPhone, "Введите корректный номер телефона");
        valid = false;
    } else showError(customerPhone, "");

    // проверка получателя (если заказчик != получатель)
    if (!sameCustomerCheckbox.checked) {
        if (recipientFio.value.trim().length < 3) {
            showError(recipientFio, "Введите корректное ФИО");
            valid = false;
        } else showError(recipientFio, "");

        if (!validatePhone(recipientPhone.value)) {
            showError(recipientPhone, "Введите корректный номер телефона");
            valid = false;
        } else showError(recipientPhone, "");
    }

    return valid;
}

payBtn.addEventListener('click', (e) => {
    e.preventDefault();

    if (!validateForm()) return; // если есть ошибки — не отправляем

    $.ajax({
        url: '/api/subscription/create',
        method: 'POST',
        data: {
            customer_fio: customerFio.value.trim(),
            customer_phone: customerPhone.value.trim(),
            recipient_fio: sameCustomerCheckbox.checked ? customerFio.value.trim() : recipientFio.value.trim(),
            recipient_phone: sameCustomerCheckbox.checked ? customerPhone.value.trim() : recipientPhone.value.trim(),
            comment: document.querySelector('#story').value.trim()
        },
        success: function(res){
            console.log(res);
            alert("Подписка успешно оформлена!");
        },
        error: function() {
            alert('Ошибка подтверждения подписки.');
        }
    });
});



 // Проверяем количество баллов
// udsButton.on('click', function(e) {
//     e.preventDefault();
//     udsResult.text('');
//     const promo = udsInput.val().trim();
//     udsButton.prop('disabled', true).text('Проверяем...');
//     $.ajax({
//         url: '/uds/check',
//         method: 'POST',
//         data: {
//             uds_promo: promo,
//             total: $('.cart__summary-total').attr('data-total'),
//             _token: $('meta[name="csrf-token"]').attr('content')
//         },
//         success: function(resp) {
//             if (resp.success) {
//                 udsResult.html('<span style="color:#71be38;">Доступно баллов: ' + resp.points + '</span>' + renderUdsActions(resp.points, promo));
//             } else {
//                 udsResult.html('<span style="color:red;">' + (resp.message || 'Ошибка проверки') + '</span>');
//             }
//         },
//         error: function() {
//             udsResult.html('<span style="color:red;">Ошибка соединения с сервером</span>');
//         },
//         complete: function() {
//             udsButton.prop('disabled', false).text('Проверить баллы');
//         }
//     });
// });