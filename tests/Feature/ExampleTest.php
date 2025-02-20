<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Http\Controllers\Api\AuthController;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

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

    public function test_forgot_password_with_valid_email()
    {
        // Cria um usuário de teste
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        // Simula uma requisição com o email válido
        $request = new Request([
            'email' => 'test@example.com'
        ]);

        $controller = new AuthController();
        $response = $controller->forgot_password($request);

        // Verifica se a resposta é um JSON com o status esperado
        $this->assertEquals(200, $response->status());
        $this->assertJson($response->content());
        $this->assertEquals(['status' => __(Password::RESET_LINK_SENT)], $response->getData(true));
    }

    public function test_forgot_password_with_invalid_email()
    {
        // Simula uma requisição com um email inválido
        $request = new Request([
            'email' => 'nonexistent@example.com'
        ]);

        $controller = new AuthController();
        $response = $controller->forgot_password($request);

        // Verifica se a resposta é um JSON com a mensagem de erro esperada
        $this->assertEquals(200, $response->status());
        $this->assertJson($response->content());
        $this->assertEquals(['message' => 'Usuário não encontrado.'], $response->getData(true));
    }

    public function test_reset_password_with_valid_data()
    {
        // Cria um usuário de teste
        $user = User::factory()->create([
            'email' => 'test2@example.com',
            'password' => Hash::make('old_password')
        ]);

        // Cria um token de redefinição de senha
        $token = Password::createToken($user);

        // Simula uma requisição com dados válidos
        $request = new UpdateUserPasswordRequest([
            'email' => 'test2@example.com',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
            'token' => $token
        ]);

        // Simula a validação da requisição
        $request->setContainer(app())->validateResolved();

        $controller = new AuthController();
        $response = $controller->reset_password($request);

        // Verifica se a resposta é um JSON com o status esperado
        $this->assertEquals(200, $response->status());
        $this->assertJson($response->content());
        $this->assertEquals(['status' => __(Password::PASSWORD_RESET)], $response->getData(true));

        // Verifica se a senha foi atualizada
        $user->refresh();
        $this->assertTrue(Hash::check('new_password', $user->password));
    }

    public function test_reset_password_with_invalid_data()
    {
        // Simula uma requisição com um token inválido
        $request = new UpdateUserPasswordRequest([
            'email' => 'test2@example.com',
            'password' => 'new2_password',
            'password_confirmation' => 'new2_password',
            'token' => 'invalid_token'
        ]);

        // Simula a validação da requisição
        $request->setContainer(app())->validateResolved();

        $controller = new AuthController();
        $response = $controller->reset_password($request);

        // Verifica se a resposta é um JSON com o status esperado
        $this->assertEquals(200, $response->status());
        $this->assertJson($response->content());

        // O status esperado é "This password reset token is invalid."
        $this->assertEquals(['status' => __(Password::INVALID_TOKEN)], $response->getData(true));
    }
}
