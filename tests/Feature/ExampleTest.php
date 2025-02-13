<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('api/');

        $response->assertStatus(200);
    }

    public function test_user_can_soft_delete_their_account()
    {
        //arrange
        $user = User::factory()->create();
        $account = $user->account()->create([
            'name' => 'Test Account',
            'balance' => 0,
        ]);

        //act
        $response = $this->actingAs($user)->delete('api/accounts/' . $account->id);

        //assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_user_can_not_soft_delete_their_account_if_balance_is_not_zero()
    {
        //arrange
        $user = User::factory()->create();
        $account = $user->account()->create([
            'name' => 'Test Account',
            'balance' => 1000,
        ]);

        //act
        $response = $this->actingAs($user)->delete('api/accounts/' . $account->id);

        //assert
        $response->assertStatus(400);
        $this->assertNotSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_user_can_not_soft_delete_anothers_account()
    {
        //arrange
        $user = User::factory()->create();
        $account = User::factory()->create()->account()->create([
            'name' => 'Test Account',
            'balance' => 0,
        ]);

        //act
        $response = $this->actingAs($user)->delete('api/accounts/' . $account->id);

        //assert
        $response->assertStatus(403);
        $this->assertNotSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_user_can_restore_their_account()
    {
        //arrange
        $user = User::factory()->create();
        $account = $user->account()->create([
            'name' => 'Test Account',
            'balance' => 0,
            'deleted_at' => now(),
        ]);

        //act
        $response = $this->actingAs($user)->post('api/accounts/' . $account->id . '/restore');

        //assert
        $response->assertStatus(200);
        $this->assertNotSoftDeleted('accounts', ['id' => $account->id]);
    }
}
