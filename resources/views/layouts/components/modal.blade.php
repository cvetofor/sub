@php
    use App\Models\City;
    $cities = City::active()->get();
    $selectedCityId = session('city_id') ?? null;
@endphp

<div id="city-modal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur bg-white/70 hidden">
    <div class="bg-white h-[350px] w-full max-w-3xl p-6 rounded-3xl shadow-xl border border-rose-300 relative">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-semibold">Выбор города</h2>
            <button
                class="w-10 h-10 flex items-center justify-center rounded-full bg-rose-200 hover:bg-rose-200 transition cursor-pointer"
                onclick="closeCityModal()">
                ✕
            </button>
        </div>
        <input type="text" id="city-search" placeholder="Введите название города..."
            class="w-full p-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-400 outline-none"
            onkeyup="filterCities()">
        <ul
            class="grid grid-cols-1 md:grid-cols-3 gap-x-[26px] gap-y-[15px] max-h-[calc(90vh-100px)] px-5 pb-2.5 mt-7 overflow-auto">
            @foreach ($cities as $city)
                <li class="city font-semibold hover:text-rose-600 cursor-pointer" data-city-id="{{ $city->id }}">
                    {{ $city->name }}
                </li>
            @endforeach
        </ul>

    </div>
</div>

<script>
    function openCityModal() {
        $('#city-modal').removeClass('hidden');
    }

    function closeCityModal() {
        $('#city-modal').addClass('hidden');
    }

    function filterCities() {
        const filter = $('#city-search').val().toLowerCase();

        $('#city-modal ul li').each(function() {
            const cityName = $(this).text().toLowerCase();
            $(this).toggle(cityName.indexOf(filter) > -1);
        });
    }

    $('#city-modal .city').on('click', function() {
        const cityId = $(this).data('city-id');

        $.ajax({
            url: "{{ route('city.set') }}",
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            contentType: "application/json",
            data: JSON.stringify({
                city_id: cityId
            }),
            success: function(data) {
                if (data.success) {
                    closeCityModal();
                    location.reload();
                }
            }
        });
    });

    @if (!$selectedCityId)
        window.addEventListener('DOMContentLoaded', () => {
            openCityModal();
        });
    @endif
</script>
