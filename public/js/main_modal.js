$(document).ready(function () {
    const sameCustomerCheckbox = $('#same_customer');
    const recipientFio = $('#recipientFio');
    const recipientPhone = $('#recipientPhone');

    sameCustomerCheckbox.on('change', function () {
        const isChecked = $(this).is(':checked');
        const fields = [recipientFio, recipientPhone];

        fields.forEach(el => {
            if (isChecked) {
                el.addClass('max-h-0 opacity-0 overflow-hidden')
                    .removeClass('max-h-40 opacity-100');
                const input = el.find('input');
                input.val('');
            } else {
                el.removeClass('max-h-0 opacity-0 overflow-hidden')
                    .addClass('max-h-40 opacity-100');
            }
        });
    });

    const payBtn = $('#payBtn');
    const senderFio = $('#senderFio input');
    const recipientFioInput = $('#recipientFio input');
    const senderPhone = $('#senderPhone input');
    const recipientPhoneInput = $('#recipientPhone input');
    const comment = $('#textAreaCommentSub');

    payBtn.on('click', function () {
        const isVsiblePlan = !$('#readyPlansWrapper').hasClass('hidden');
        let requestData = {};

        if (isVsiblePlan) {
            const activePlan = $('.active-plan');
            // Частота берётся из выпадающего списка именно выбранной карточки
            const frequency = activePlan.find('.frequency').val() ?? $('.frequency').first().val();

            requestData = {
                sender_name: senderFio.val(),
                receiving_name: recipientFioInput.val(),
                sender_phone: senderPhone.val(),
                receiving_phone: recipientPhoneInput.val(),
                frequency: frequency,
                comment: comment.val(),
                is_custom: false,
                plan_id: activePlan.length ? activePlan.data('plan-id') : ''
            };
        } else {
            // В конструкторе частота хранится в data-атрибуте блока «Ваш выбор»
            const frequency = $('#frequencyOutputAside').data('frequency') ?? 'weekly';
            const timeDelivery = $('#timeSelect');
            const address = $('#deliveryAddress');
            const using_promo = $('#checkboxPromo');
            const totalPrice = $('#totalAmountElement');
            const city = $('#citySelect');
            const options = $('.option');
            const optionsValue = options.map(function () {
                if ($(this).is(':checkbox')) {
                    if ($(this).is(':checked')) {
                        return $(this).val();
                    } else {
                        return null;
                    }
                } else {
                    return $(this).val();
                }
            }).get();

            requestData = {
                time_delivery: timeDelivery.val(),
                sender_name: senderFio.val(),
                receiving_name: recipientFioInput.val(),
                sender_phone: senderPhone.val(),
                receiving_phone: recipientPhoneInput.val(),
                address: address.val(),
                frequency: frequency,
                comment: comment.val(),
                using_promo: using_promo.length ? using_promo.is(':checked') : false,
                is_custom: true,
                plan_id: null,
                price: totalPrice.data('total'),
                city_id: city.val(),
                option_ids: optionsValue
            };
        }

        $.ajax({
            url: '/api/subscription/create',
            method: 'POST',
            data: requestData,
            success: function (response) {
                if (response.payment_link) {
                    window.location.href = response.payment_link;
                } else {
                    alert(response.message || 'Не удалось создать оплату. Попробуйте ещё раз.');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                    const errors = jqXHR.responseJSON.errors;
                    let errorMessages = 'Пожалуйста, исправьте следующие ошибки:\n\n';

                    for (let field in errors) {
                            errorMessages += `${errors[field].join(', ')}\n`;
                    }

                    alert(errorMessages);
                } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    alert(jqXHR.responseJSON.message);
                } else {
                    alert('Ошибка подтверждения подписки: ' + errorThrown);
                }
            }
        });
    });
});
