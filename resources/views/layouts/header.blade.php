@php
    use App\Models\City;
    $cities = City::active()->get();
@endphp

<header class="sticky top-0 z-30 backdrop-blur bg-white/70 border-b border-rose-100">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
        <img src="/images/logo.svg" alt="logo" width="140px">
        <div class="flex items-center gap-3">
            <select class="px-3 py-2 rounded-xl border border-rose-200 bg-white shadow-sm cursor-pointer" name="select_city" id="select_city">
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 rounded-xl bg-rose-600 text-white shadow cursor-pointer" type="button">Собрать подписку</button>
        </div>
    </div>
</header>

