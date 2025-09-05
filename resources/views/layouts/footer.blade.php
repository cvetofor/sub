<footer class="border-t border-rose-200 bg-white">
  <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-gray-600 grid gap-6 md:grid-cols-2">

    <!-- Левая часть: контакты + виджет -->
    <div class="flex flex-col md:flex-row gap-4 content-center">
      <iframe frameborder="0" class="h-[50px] md:w-40 md:h-16 flex-shrink-0"
        src="https://widget.2gis.ru/api/widget?org_id=70000001047408570&amp;branch_id=70000001094519097&amp;size=medium&amp;theme=light"></iframe>
      
      <div class="flex flex-col gap-1">
        <p>г. Улан-Удэ, ул. Геологическая 11А</p>
        <p>ИП Берков М. В. ИНН: 032385290437 ОГРНИП: 312032724200091</p>
        <p class="font-semibold">Телефон: +7 301 250-22-20</p>
      </div>
    </div>

    <!-- Правая часть: ссылки и копирайт -->
    <div class="flex flex-col justify-between items-start md:items-end gap-2">
      <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
        <a href="https://xn--b1ag1aakjpl.xn--p1ai/policy" target="_blank" class="hover:underline">Политика конфиденциальности</a>
        <a href="{{ route('user_agreement') }}" class="hover:underline">Пользовательское соглашение</a>
      </div>
      <p class="font-semibold">© Цветофор, {{ date('Y') }}</p>
    </div>

  </div>
</footer>
