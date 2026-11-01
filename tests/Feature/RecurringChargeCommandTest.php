<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringChargeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_recurrent_charge_is_only_a_preflight_without_matching_confirmation(): void
    {
        $city = City::create(['name' => 'Ярославль', 'code' => 'yar', 'is_active' => true]);
        $plan = Plan::create([
            'name' => 'С любовью',
            'price' => 2990,
            'description' => '',
            'is_active' => true,
            'is_custom' => false,
            'city_id' => $city->id,
            'scenario' => 'any',
            'sort' => 1,
        ]);
        $subscription = Subscription::create([
            'is_active' => true,
            'plan_id' => $plan->id,
            'frequency' => 'biweekly',
            'delivery_day' => 6,
            'sender_name' => 'Анна',
            'sender_phone' => '+79123456789',
            'address' => 'Ярославль, ул. Свободы, 1',
            'next_date_payment' => now()->addMonth(),
        ]);

        $this->artisan('subscriptions:charge-one-recurrent', ['subscription' => $subscription->id])
            ->expectsOutput('subscription_id='.$subscription->id)
            ->expectsOutput('saved_payment_method=no')
            ->expectsOutput('Платёж не создан. Для адресного списания повторите номер подписки: --confirm='.$subscription->id)
            ->assertExitCode(0);

        $this->artisan('subscriptions:charge-one-recurrent', [
            'subscription' => $subscription->id,
            '--confirm' => $subscription->id,
        ])
            ->expectsOutput('Платёж не создан. Для адресного списания подтвердите дату из проверки: --due='.$subscription->next_date_payment->toDateString())
            ->assertExitCode(0);

        $this->assertDatabaseCount('payments', 0);
        $this->assertTrue((bool) $subscription->fresh()->is_active);
    }
}
