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
        const frequency = $('.toggle-btn.active').data('frequency-code');
        const isVsiblePlan = !$('#readyPlansWrapper').hasClass('hidden');
        let requestData = {};

        if (isVsiblePlan) {
            const activePlan = $('.active-plan');

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
                using_promo: using_promo.is(':checked'),
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
                console.log(response);
                alert("Подписка успешно оформлена!");
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('jqXHR:', jqXHR);
                console.log('textStatus:', textStatus);

                if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                    const errors = jqXHR.responseJSON.errors;
                    let errorMessages = '';

                    for (let field in errors) {
                        if (errors.hasOwnProperty(field)) {
                            errorMessages += `${field}: ${errors[field].join(', ')}\n`;
                        }
                    }

                    console.error('Ошибки валидации:\n' + errorMessages);
                    alert('Ошибка: \n' + errorMessages);
                } else {
                    alert('Ошибка подтверждения подписки: ' + errorThrown);
                }
            }
        });
    });
});
