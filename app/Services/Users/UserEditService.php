<?php

namespace App\Services\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\Service;
use App\Helpers\RoleHelper;
use App\Helpers\UserLimitsHelper;
use App\Http\Requests\UserRequest;
use Illuminate\Http\RedirectResponse;

class UserEditService extends Service
{
    use UserService;

    public const RESULT_CANT_MANAGE_THAT_ROLE = 0;
    public const RESULT_NEGATIVE_LIMIT = 1;
    public const RESULT_CONFIRM_PASSWORD = 2;
    public const RESULT_ENTER_PASSWORD = 3;
    public const RESULT_SUCCESS_IGNORED_PASSWORD = 4;
    public const RESULT_SUCCESS_APPLIED_PASSWORD = 5;

    private UserRequest $_request;

    public function __construct(UserRequest $request)
    {
        $this->_request = $request;
        $user = User::find($this->_request->id);

        if (!auth()->user()->role->getRolesAvailable()->pluck('id')->contains($this->_request->role_id)) {
            $this->_result = self::RESULT_CANT_MANAGE_THAT_ROLE;
            $this->_message = 'You cannot manage users with that role.';
            return;
        }

        $old_role = $user->role;
        $new_role = Role::find($this->_request->role_id);

        // Update their category limits
        [$message, $result] = UserLimitsHelper::createOrEditFromRequest($this->_request, $user);
        if (!is_null($message) && !is_null($result)) {
            $this->_message = $message;
            $this->_result = $result;
            return;
        }

        // If same role or changing from one staff role to another
        if ($old_role->id == $new_role->id || (RoleHelper::getInstance()->isStaffRole($old_role->id) && RoleHelper::getInstance()->isStaffRole($new_role->id))) {
            $user->update([
                'full_name' => $this->_request->full_name,
                'username' => $this->_request->username,
                'balance' => $this->_request->balance,
                'role_id' => $this->_request->role_id
            ]);

            $this->_result = self::RESULT_SUCCESS_IGNORED_PASSWORD;
            $this->_message = 'Updated user ' . $this->_request->full_name . '.';
            $this->_user = $user;
            return;
        }

        // Determine if their password should be kept or removed
        $password = null;
        if (!RoleHelper::getInstance()->isStaffRole($old_role->id) && RoleHelper::getInstance()->isStaffRole($new_role->id)) {
            // TODO: should be able to remove these using the UserRequest and 'confirmed' and 'requiredIf' validation rules
            if (empty($this->_request->password)) {
                $this->_result = self::RESULT_ENTER_PASSWORD;
                $this->_message = 'Please enter a password.';
                return;
            }

            if ($this->_request->password != $this->_request->password_confirmation) {
                $this->_result = self::RESULT_CONFIRM_PASSWORD;
                $this->_message = 'Please confirm the password.';
                return;
            }

            $password = bcrypt($this->_request->password);
        }

        $user->update([
            'full_name' => $this->_request->full_name,
            'username' => $this->_request->username,
            'balance' => $this->_request->balance,
            'role_id' => $this->_request->role_id,
            'password' => $password
        ]);

        $this->_result = self::RESULT_SUCCESS_APPLIED_PASSWORD;
        $this->_message = 'Updated user ' . $this->_request->full_name . '.';
        $this->_user = $user;
    }

    public function redirect(): RedirectResponse
    {
        switch ($this->getResult()) {
            case self::RESULT_SUCCESS_IGNORED_PASSWORD:
            case self::RESULT_SUCCESS_APPLIED_PASSWORD:
                return redirect()->route('users_list')->with('success', $this->getMessage());
            default:
                return redirect()->back()->withInput()->with('error', $this->getMessage());
        }
    }
}
