<div id="subscription-modal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur bg-white/70 hidden">
    <div class="bg-white w-full max-w-3xl p-6 overflow-auto rounded-3xl shadow-xl border border-rose-300">
        <h2 class="mb-4 text-center text-2xl font-semibold">Подтверждение оформления подписки</h2>

        <div class="mb-4 grid md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <h3 class="text-xl font-semibold">Контактная информация</h3>
            </div>
            <div id="senderFio">
                <label class="block text-sm text-gray-600 mb-1">ФИО заказчика</label>
                <input type="text" placeholder="ФИО" class="w-full px-3 py-2 rounded-xl border border-rose-200"
                    required>
            </div>
            <div id="senderPhone">
                <label class="block text-sm text-gray-600 mb-1">Номер телефона</label>
                <input type="text" placeholder="+7 (123) 456-78-90"
                    class="w-full px-3 py-2 rounded-xl border border-rose-200" required>
            </div>
            <div id="recipientFio" class="transition-all duration-300 ease-in-out max-h-40 opacity-100 overflow-hidden">
                <label class="block text-sm text-gray-600 mb-1">ФИО получателя</label>
                <input type="text" placeholder="ФИО" class="w-full px-3 py-2 rounded-xl border border-rose-200"
                    required>
            </div>
            <div id="recipientPhone"
                class="transition-all duration-300 ease-in-out max-h-40 opacity-100 overflow-hidden">
                <label class="block text-sm text-gray-600 mb-1">Номер телефона</label>
                <input id="inputPhone" type="text" placeholder="+7 (123) 456-78-90"
                    class="w-full px-3 py-2 rounded-xl border border-rose-200" required>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-800 mb-1">
                <input type="checkbox" id="same_customer" value="true">Заказчик и получатель совпадает
            </label>

        </div>

        <div class="mb-4">
            <label class="block text-sm text-gray-600 mb-1">Комментарий</label>
            <textarea id="textAreaCommentSub" class="w-full px-3 py-2 rounded-xl border border-rose-200" name="textAreaCommentSub"
                placeholder="Начните писать..." rows="3"></textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <button id="payBtn"
                class="px-4 py-2 rounded-full text-white cursor-pointer transition-all duration-200 bg-rose-600">Перейти
                к оплате</button>
            <button id="closeBtn"
                class="px-4 py-2 rounded-full border border-rose-400 text-rose-700 cursor-pointer transition-all duration-200 bg-rose-100 ">Отмена</button>
        </div>

        <p class="mt-4 text-sm text-gray-600">*Нажимая на "Перейти к оплате" вы соглашаетесь с <a
                href="{{ route('policy') }}" target="_blank" class="text-rose-700">публичной офертой</a>.</p>
    </div>
</div>

<script>
    $(function() {
        $("#senderPhone input").mask("+7 (999) 999-9999");
    });
    $(function() {
        $("#recipientPhone input").mask("+7 (999) 999-9999");
    });
</script>

<script src="{{ asset('js/main_modal.js') }}"></script>
