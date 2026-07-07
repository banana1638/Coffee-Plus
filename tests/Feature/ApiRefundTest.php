<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_refunds(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $refund = new Transaction();
        $refund->user_id = $user->id;
        $refund->bill_id = 'CP-REFUND-MINE';
        $refund->oz_delta = 0;
        $refund->type = 'refund';
        $refund->description = 'Cancelled order refund';
        $refund->save();

        $otherRefund = new Transaction();
        $otherRefund->user_id = $otherUser->id;
        $otherRefund->bill_id = 'CP-REFUND-OTHER';
        $otherRefund->oz_delta = 0;
        $otherRefund->type = 'refund';
        $otherRefund->description = 'Other refund';
        $otherRefund->save();

        $this->actingAs($user)
            ->getJson('/api/refunds')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('refunds.0.bill_id', 'CP-REFUND-MINE')
            ->assertJsonMissingPath('refunds.0.order_details')
            ->assertJsonMissing(['bill_id' => 'CP-REFUND-OTHER']);
    }

    public function test_user_can_search_refunds_by_bill_id(): void
    {
        $user = User::factory()->create();

        foreach (['CP-MATCH', 'CP-OTHER'] as $billId) {
            $refund = new Transaction();
            $refund->user_id = $user->id;
            $refund->bill_id = $billId;
            $refund->oz_delta = 0;
            $refund->type = 'refund';
            $refund->description = "Refund {$billId}";
            $refund->save();
        }

        $this->actingAs($user)
            ->getJson('/api/refunds?search_id=MATCH')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('refunds.0.bill_id', 'CP-MATCH')
            ->assertJsonMissing(['bill_id' => 'CP-OTHER']);
    }
}
