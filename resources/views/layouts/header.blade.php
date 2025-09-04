@php
    use App\Models\City;
    $cities = City::active()->get();
    $selectedCityId = session('city_id');
@endphp

<header class="sticky top-0 z-30 backdrop-blur bg-white/70 border-b border-rose-100">
    <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
        <div class="flex gap-4">
            <a href="/"><img src="{{ asset('/images/logo.svg') }}" alt="logo" width="140px"></a>
            <iframe frameborder="0" width="150px" height="50px"
                src="https://widget.2gis.ru/api/widget?org_id=70000001047408570&amp;branch_id=70000001094519097&amp;size=medium&amp;theme=light"></iframe>
        </div>
        <div class="flex items-center gap-3">
            <select class="px-3 py-2 rounded-xl border border-rose-200 bg-white shadow-sm cursor-pointer"
                name="select_city" id="select_city">
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" {{ $selectedCityId == $city->id ? 'selected' : '' }}>
                        {{ $city->name }}
                    </option>
                @endforeach
            </select>
            <button class="px-4 py-2 rounded-xl bg-rose-600 text-white shadow cursor-pointer" id="subscribeBtn"
                type="button">Собрать подписку</button>
        </div>
    </div>

    <script>
        document.getElementById('select_city').addEventListener('change', function() {
            const cityId = this.value;
            fetch("{{ route('city.set') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        city_id: cityId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                })
        });
    </script>
</header>
