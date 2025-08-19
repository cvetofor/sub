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

payBtn.addEventListener('click', () => {

});

