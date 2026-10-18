{{-- Герой B: общий заголовок и две фото-карточки выбора, ничего не выбрано --}}
<section class="hero hero-b">
    <div class="wrap grid">
        <div class="col">
            <h1 class="serif rise" id="heroTitle">Цветы, которые <em>приходят сами</em></h1>
            <p class="lead rise d1" id="heroLead">Свежий букет раз в неделю, в две или в месяц. От 1 999 ₽ за букет, второй в подарок.</p>
            <div class="cta rise d3">
                <a class="btn" href="{{ route('wizard') }}" id="heroCta" data-track="wizard_start" data-source="hero">Собрать подписку</a>
                <a class="btn ghost" href="#plans">Посмотреть планы</a>
            </div>
            <div class="meta rise d4">
                <span class="badge">4,9 в 2ГИС</span>
                <span>Улан-Удэ · пауза без штрафов</span>
                <span>первый букет в субботу, {{ $firstBouquetDate->translatedFormat('j F') }}</span>
            </div>
        </div>
        <div class="choose rise d2">
            <div class="choose-label" id="chooseLabel">Для кого цветы?</div>
            <div class="choice-cards" role="radiogroup" aria-labelledby="chooseLabel" id="modeCards">
                <button type="button" class="choice" role="radio" aria-checked="false" data-mode="home">
                    <span class="ph"><img src="{{ asset('images/site/hero-home.jpg') }}" alt="" width="1000" height="1250" fetchpriority="high"><span class="check" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span></span>
                    <span class="cap"><b>Себе домой</b><small>в вазу, без упаковки</small></span>
                </button>
                <button type="button" class="choice" role="radio" aria-checked="false" data-mode="gift">
                    <span class="ph"><img src="{{ asset('images/site/hero-gift.jpg') }}" alt="" width="1000" height="1250" fetchpriority="high" style="object-position:50% 35%"><span class="check" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span></span>
                    <span class="cap"><b>В подарок</b><small>в руки, с открыткой</small></span>
                </button>
            </div>
            <div class="choose-hint" id="chooseHint" aria-live="polite"></div>
        </div>
    </div>
</section>
