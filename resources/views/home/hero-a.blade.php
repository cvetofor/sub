{{-- Герой A: сегментный переключатель над заголовком, по умолчанию «Себе домой» --}}
<section class="hero hero-a">
    <div class="wrap grid">
        <div class="col">
            <div class="seg two rise" role="group" aria-label="Для кого цветы" id="heroSeg">
                <div class="pill" aria-hidden="true"></div>
                <button type="button" data-mode="home" aria-pressed="true">Себе домой</button>
                <button type="button" data-mode="gift" aria-pressed="false">В подарок</button>
            </div>
            <h1 class="serif rise d1" id="heroTitle">Пусть в вазе всегда <em>что&#8209;то стоит</em></h1>
            <div class="meta rise d2"><span class="badge">Второй букет в подарок</span><span>от 1 999 ₽ за букет · Улан-Удэ · 4,9 в 2ГИС</span></div>
            <p class="lead rise d2" id="heroLead">Свежие цветы приходят домой сами: раз в неделю, в две или в месяц. Без упаковки, сразу в вазу. Пауза в один тап.</p>
            <div class="cta rise d3">
                <a class="btn" href="{{ route('wizard', ['for' => 'home']) }}" id="heroCta" data-track="wizard_start" data-source="hero">Собрать подписку</a>
                <a class="btn ghost" href="#plans">Посмотреть планы</a>
            </div>
            <div class="meta rise d4"><span>Первый букет: в субботу, {{ $firstBouquetDate->translatedFormat('j F') }}</span></div>
        </div>
        <div class="visual">
            <div class="blob breathe" id="heroBlob" aria-hidden="true"></div>
            <img class="photo-in" id="heroImg" src="{{ asset('images/site/hero-home.jpg') }}" alt="Цветы в стеклянной вазе на деревянном столе" width="1000" height="1250" fetchpriority="high">
        </div>
    </div>
</section>
