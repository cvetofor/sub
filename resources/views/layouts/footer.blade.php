<footer class="border-t border-rose-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-gray-600 grid md:grid-cols-2 gap-4">
        <div class="flex gap-4">
            <iframe frameborder="0" width="150px" height="50px"
                src="https://widget.2gis.ru/api/widget?org_id=70000001047408570&amp;branch_id=70000001094519097&amp;size=medium&amp;theme=light"></iframe>
            <div class="flex flex-col w-full">
                <p> г. Улан-Удэ, ул. Геологическая 11А</p>
                <p> ИП Берков М. В. ИНН: 032385290437 ОГРНИП: 312032724200091</p>
                <p class="font-semibold"> Телефон: +73012502220</p>
            </div>
        </div>

        <div class="flex flex-col justify-between">
            <div class="flex justify-end gap-4">
                <a href="https://xn--b1ag1aakjpl.xn--p1ai/policy" target="_blank">Политика конфиденциальности</a>
                <a href="{{ route('user_agreement') }}">Пользовательское соглашение</a>
            </div>
            <p class="flex justify-end font-semibold">© Цветофор, {{ date('Y') }}</p>
        </div>
</footer>
