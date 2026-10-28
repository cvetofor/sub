<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Проверка адреса доставки по подсказкам DaData: адрес должен находиться в городе доставки
 * и содержать улицу и дом. Нужен DADATA_TOKEN в окружении; без него проверка пропускается.
 * Если сервис недоступен, заказ не блокируем: адрес проверит флорист.
 */
class AddressCheck {
    public const OK = 'ok';
    public const OUTSIDE = 'outside';
    public const NO_HOUSE = 'no_house';
    public const SKIPPED = 'skipped';

    public static function configured(): bool {
        return (bool) config('services.dadata.token');
    }

    public static function city(): ?string {
        return City::active()->value('name');
    }

    /**
     * Проверить адрес. Возвращает ['status' => ok|outside|no_house|skipped, 'address' => нормализованный адрес или null].
     * Код дома (fias), выбранный из подсказок на сайте, ускоряет проверку: ищем сразу по нему.
     */
    public static function verify(string $address, ?string $fias = null): array {
        $city = self::city();
        if (!self::configured() || !$city) {
            return ['status' => self::SKIPPED, 'address' => null];
        }
        try {
            $client = Http::withHeaders(['Authorization' => 'Token ' . config('services.dadata.token')])->acceptJson()->timeout(5);
            $url = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/';
            $suggestions = [];
            if ($fias && preg_match('/^[0-9a-f-]{36}$/', $fias)) {
                $suggestions = $client->post($url . 'findById/address', ['query' => $fias])->throw()->json('suggestions') ?? [];
            }
            if (!$suggestions) {
                $suggestions = $client->post($url . 'suggest/address', [
                    'query' => $address,
                    'count' => 3,
                    'locations' => [['city' => $city]],
                    'restrict_value' => true,
                ])->throw()->json('suggestions') ?? [];
            }
        } catch (\Throwable $e) {
            Log::channel('shop')->warning('Проверка адреса недоступна, пропущена.', ['address' => $address, 'message' => $e->getMessage()]);

            return ['status' => self::SKIPPED, 'address' => null];
        }

        $best = $suggestions[0] ?? null;
        if (!$best) {
            return ['status' => self::OUTSIDE, 'address' => null];
        }
        $data = $best['data'] ?? [];
        $inCity = mb_strtolower((string) ($data['city'] ?? $data['settlement'] ?? '')) === mb_strtolower($city);
        if (!$inCity) {
            return ['status' => self::OUTSIDE, 'address' => $best['value'] ?? null];
        }
        if (empty($data['house'])) {
            return ['status' => self::NO_HOUSE, 'address' => $best['value'] ?? null];
        }

        return ['status' => self::OK, 'address' => $best['value'] ?? $address];
    }

    /** Текст ошибки для клиента или null, если адрес подходит. */
    public static function problem(array $result): ?string {
        $city = self::city() ?: 'нашему городу';

        return match ($result['status']) {
            self::OUTSIDE => 'Пока доставляем только по городу ' . $city . '. Проверьте улицу и дом.',
            self::NO_HOUSE => 'Укажите номер дома: по одной улице курьер не найдёт.',
            default => null,
        };
    }
}
