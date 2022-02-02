<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserDeleteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function testCanDeleteUser(): void
    {
        $user = $this->createUser();

        $userService = new UserDeleteService($user->id);

        $this->assertSame(UserDeleteService::RESULT_SUCCESS, $userService->getResult());
        $this->assertTrue($user->refresh()->trashed());
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::factory()->create()
        ]);
    }
}
