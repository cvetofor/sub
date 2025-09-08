@php
    use App\Models\City;
    $cities = City::active()->get();
    $selectedCityId = session('city_id') ?? 2;

    $isAuth = Auth::check();
@endphp

<header class="sticky top-0 z-30 backdrop-blur bg-white/70 border-b border-rose-100">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
        <div class="flex gap-4">
            <a href="/"><img src="{{ asset('/images/logo.svg') }}" alt="logo" width="140px"></a>
            <iframe frameborder="0" width="150px" height="50px"
                src="https://widget.2gis.ru/api/widget?org_id=70000001047408570&amp;branch_id=70000001094519097&amp;size=medium&amp;theme=light"></iframe>
        </div>
        <div class="flex items-center gap-4">
            <button id="openModal" onclick="openCityModal()"
                class="flex items-center gap-2 font-semibold cursor-pointer text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600" viewBox="0 0 24 24"
                    fill="currentColor">
                    <path
                        d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z" />
                </svg>
                <span class="underline underline-offset-4 decoration-dotted decoration-rose-600">
                    {{ $cities->where('id', $selectedCityId)->first()->name }}
                </span>
            </button>

            <a class="px-4 py-2 rounded-xl bg-rose-600 text-white shadow cursor-pointer"
                href="{{ $isAuth && request()->is('profile') ? route('logout') : route('profile') }}">
                {{ $isAuth && request()->is('profile') ? 'Выйти' : 'Войти в ЛК' }}
            </a>
            </a>
        </div>
    </div>
</header>
