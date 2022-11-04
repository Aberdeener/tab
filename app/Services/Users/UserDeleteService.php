<?php

namespace App\Services\Users;

use App\Models\User;
use App\Services\Service;
use Illuminate\Http\RedirectResponse;

class UserDeleteService extends Service
{
    use UserService;

    public const RESULT_SUCCESS = 0;

    public function __construct(User $user)
    {
        $this->_user = $user;

        $user->delete();

        $this->_result = self::RESULT_SUCCESS;
        $this->_message = "Deleted user $user->full_name";
    }

    public function redirect(): RedirectResponse
    {
        return match ($this->getResult()) {
            self::RESULT_SUCCESS => redirect()->route('users_list')->with('success', $this->getMessage()),
            default => redirect()->route('users_list')->with('error', $this->getMessage()),
        };
    }
}
