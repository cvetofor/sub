<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase {
    public function test_normalizes_russian_mobile_numbers(): void {
        $this->assertSame('+79123456789', Phone::normalize('+7 (912) 345-67-89'));
        $this->assertSame('+79123456789', Phone::normalize('8 912 345 67 89'));
        $this->assertSame('+79123456789', Phone::normalize('9123456789'));
        $this->assertSame('+79123456789', Phone::normalize('+7 (912) 345-6789')); // старый формат сайта
    }

    public function test_rejects_non_mobile_or_foreign_numbers(): void {
        $this->assertNull(Phone::normalize('+7 (495) 123-45-67')); // городской
        $this->assertNull(Phone::normalize('+380 50 123 45 67'));
        $this->assertNull(Phone::normalize('12345'));
        $this->assertNull(Phone::normalize(''));
        $this->assertNull(Phone::normalize(null));
    }

    public function test_formats(): void {
        $this->assertSame('+7 (912) 345-67-89', Phone::pretty('+79123456789'));
        $this->assertSame('+7 (912) 345-6789', Phone::legacy('+79123456789'));
    }
}
