@extends('layouts.app')

@section('title', 'Политика конфиденциальности')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-10">
    <div class="p-6 rounded-3xl border border-rose-200 bg-white shadow-sm">
        <h1 class="text-3xl md:text-4xl font-extrabold mb-6">Политика конфиденциальности</h1>

        <p class="text-gray-700 mb-4">Мы стремимся обеспечить высокий уровень качества нашей продукции и сервиса. Если у
            вас возникли проблемы с полученным заказом, ознакомьтесь с условиями возврата и компенсации.</p>

        <h2 class="text-xl font-semibold mt-6 mb-2">1. Возврат товара</h2>
        <p class="text-gray-700 mb-4">Возврат возможен в течение 24 часов с момента получения заказа, если товар имеет
            существенные недостатки, повреждения или не соответствует описанию.</p>

        <h2 class="text-xl font-semibold mt-6 mb-2">2. Возмещение</h2>
        <p class="text-gray-700 mb-4">В случае подтверждения претензии мы предлагаем:</p>
        <ul class="list-disc pl-6 space-y-2 text-gray-700 text-sm">
            <li>замену товара на аналогичный;</li>
            <li>предоставление скидки или бонуса;</li>
            <li>возврат полной стоимости заказа.</li>
        </ul>

        <h2 class="text-xl font-semibold mt-6 mb-2">3. Исключения</h2>
        <p class="text-gray-700 mb-4">Возврат не осуществляется, если:</p>
        <ul class="list-disc pl-6 space-y-2 text-gray-700 text-sm">
            <li>продукция утратила товарный вид по вине получателя;</li>
            <li>растения/цветы были испорчены из-за неправильного ухода;</li>
            <li>прошло более 24 часов с момента получения заказа.</li>
        </ul>

        <h2 class="text-xl font-semibold mt-6 mb-2">4. Порядок оформления возврата</h2>
        <p class="text-gray-700 mb-4">Для возврата или компенсации необходимо обратиться в службу поддержки, указав
            номер заказа и приложив фото товара.</p>

        <h2 class="text-xl font-semibold mt-6 mb-2">5. Контакты</h2>
        <p class="text-gray-700">По вопросам возврата и компенсации свяжитесь с нами через <a
                href="mailto:support@cvetofor.ru" class="text-rose-600 underline">support@cvetofor.ru</a> или по
            телефону <span class="font-medium">+7 (999) 123-45-67</span>.</p>
    </div>
</section>
@endsection
