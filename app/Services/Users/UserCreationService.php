<?php

namespace App\Services\Users;

use DB;
use App\Models\User;
use App\Services\Service;
use App\Helpers\RoleHelper;
use App\Helpers\UserLimitsHelper;
use App\Http\Requests\UserRequest;
use Illuminate\Http\RedirectResponse;

class UserCreationService extends Service
{
    use UserService;

    public const RESULT_INVALID_LIMIT = 0;
    public const RESULT_SUCCESS = 1;

    public function __construct(
        private UserRequest $_request
    ) {
        $user = new User();
        $user->full_name = $this->_request->full_name;
        $user->username = $this->_request->username ?: strtolower(str_replace(' ', '', $this->_request->full_name));

        // TODO: Edge case: Their full name was unique, they did not enter a custom username, but the autogenerated username above is not unique
        // Possible Cause: "Tad hgBoyle" as full name instead of "Tadhg Boyle" -> would make "tadhgboyle" as username, which might already exist
        // Three options:
        // 1. Leave as is. Slightly slower as we need another query, but it works
        // 2. Instead of replacing whitespace with "" when making the username, replace it with "_"
        // 3. Always add a random number to the end of all autogenerated usernames

        // Using User::where(...)->count() does not work while testing for some reason
        if (DB::table('users')->where('username', $user->username)->count() > 0) {
            $user->username = $user->username . mt_rand(0, 100);
        }

        $user->balance = $this->_request->balance ?: 0;
        $user->role_id = $this->_request->role_id;

        if (RoleHelper::getInstance()->isStaffRole($this->_request->role_id)) {
            $user->password = bcrypt($this->_request->password);
        }

        $user->save();

        // Update their category limits
        [$message, $result] = UserLimitsHelper::createOrEditFromRequest($this->_request, $user, $this::class);
        if (!is_null($message) && !is_null($result)) {
            $this->_message = $message;
            $this->_result = $result;
            return;
        }

        $this->_result = self::RESULT_SUCCESS;
        $this->_message = "Created user {$user->full_name}";
        $this->_user = $user;
    }

    public function redirect(): RedirectResponse
    {
        switch ($this->getResult()) {
            case self::RESULT_SUCCESS:
                return redirect()->route('users_list')->with('success', $this->getMessage());
            default:
                return redirect()->back()->withInput()->with('error', $this->getMessage());
        }
    }
}
