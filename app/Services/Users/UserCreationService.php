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

    public function __construct(UserRequest $request)
    {
        $user = new User();
        $user->full_name = $request->full_name;
        $user->username = $request->username ?: strtolower(str_replace(' ', '', $request->full_name));

        // TODO: Edge case: Their full name was unique, they did not enter a custom username, but the autogenerated username above is not unique
        // Possible Cause: "Tad hgBoyle" as full name instead of "Tadhg Boyle" -> would make "tadhgboyle" as username, which might already exist
        // Three options:
        // 1. Leave as is. Slightly slower as we need another query, but it works
        // 2. Instead of replacing whitespace with "" when making the username, replace it with "_"
        // 3. Always add a random number to the end of all autogenerated usernames

        // Using User::where(...)->count() does not work while testing for some reason
        if (DB::table('users')->where('username', $user->username)->count() > 0) {
            $user->username .= random_int(0, 100);
        }

        $user->balance = $request->balance ?: 0;
        $user->role_id = $request->role_id;

        if (resolve(RoleHelper::class)->isStaffRole($request->role_id)) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        // has to be after save() so they have an id
        foreach ($request->rotations as $rotation_id) {
            $user->rotations()->attach($rotation_id);
        }

        // Update their category limits
        [$message, $result] = UserLimitsHelper::createOrEditFromRequest($request, $user, self::class);
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
        return match ($this->getResult()) {
            self::RESULT_SUCCESS => redirect()->route('users_list')->with('success', $this->getMessage()),
            default => redirect()->back()->with('error', $this->getMessage()),
        };
    }
}
