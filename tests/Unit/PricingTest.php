<?php

namespace Tests\Unit;

use App\Services\Pricing;
use PHPUnit\Framework\TestCase;

class PricingTest extends TestCase {
    public function test_first_month_applies_second_bouquet_free(): void {
        // Каждую неделю: 4 букета по цене 3
        $this->assertSame(9000.0, Pricing::firstMonth(3000, Pricing::WEEKLY));
        // Раз в 2 недели: 2 букета по цене 1
        $this->assertSame(3000.0, Pricing::firstMonth(3000, Pricing::BIWEEKLY));
        // Раз в месяц: в первый месяц два букета по цене одного
        $this->assertSame(3000.0, Pricing::firstMonth(3000, Pricing::MONTHLY));
    }

    public function test_monthly_is_full_price_without_promo(): void {
        $this->assertSame(12000.0, Pricing::monthly(3000, Pricing::WEEKLY));
        $this->assertSame(6000.0, Pricing::monthly(3000, Pricing::BIWEEKLY));
        $this->assertSame(3000.0, Pricing::monthly(3000, Pricing::MONTHLY));
    }

    public function test_extras_are_counted_per_delivery_and_once(): void {
        // Фото 69 ₽ к каждой доставке, ваза 2000 ₽ разово; раз в 2 недели платим за 1 доставку в первый месяц
        $this->assertSame(3000.0 + 69 + 2000, Pricing::firstMonth(3000, Pricing::BIWEEKLY, 2000, 69));
        $this->assertSame((3000.0 + 69) * 2, Pricing::monthly(3000, Pricing::BIWEEKLY, 69));
        // Каждую неделю: опция «к каждой доставке» в первый месяц берётся 3 раза (за оплаченные)
        $this->assertSame((3000.0 + 69) * 3 + 2000, Pricing::firstMonth(3000, Pricing::WEEKLY, 2000, 69));
    }

    public function test_frequencies_and_validation(): void {
        $this->assertSame(['weekly', 'biweekly', 'monthly'], Pricing::frequencies());
        $this->assertTrue(Pricing::isValid('weekly'));
        $this->assertFalse(Pricing::isValid('daily'));
        $this->assertFalse(Pricing::isValid(null));
        $this->assertArrayHasKey('firstPaid', Pricing::table()['weekly']);
    }
}
