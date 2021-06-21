<?php

namespace App\Http\Controllers;

use Cookie;
use App\Models\User;
use App\Helpers\CategoryHelper;
use App\Helpers\RotationHelper;
use App\Helpers\UserLimitsHelper;
use App\Http\Requests\UserRequest;
use App\Services\Users\UserEditService;
use App\Services\Users\UserDeleteService;
use App\Services\Users\UserCreationService;

class UserController extends Controller
{
    public function new(UserRequest $request)
    {
        return (new UserCreationService($request))->redirect();
    }

    public function edit(UserRequest $request)
    {
        return (new UserEditService($request))->redirect();
    }

    public function delete(int $user_id)
    {
        return (new UserDeleteService($user_id))->redirect();
    }

    public function list()
    {
        $current_rotation = RotationHelper::getInstance()->getCurrentRotation();
        $selected_rotation = hasPermission('users_list_select_rotation') ? Cookie::get('user_list_rotation', $current_rotation?->id) : $current_rotation?->id;

        if ($selected_rotation == null || $selected_rotation == 'all') {
            $users = User::all();
        } else {
            $users = User::whereHas('rotations', function ($query) use ($selected_rotation) {
                $query->where('rotation_id', $selected_rotation);
            })->get();
        }

        return view('pages.users.list', [
            'users' => $users,
            'rotations' => RotationHelper::getInstance()->getRotations(),
            'current_rotation' => $current_rotation,
            'selected_rotation' => $selected_rotation,
        ]);
    }

    public function view()
    {
        $user = User::find(request()->route('id'));
        if ($user == null) {
            return redirect()->route('users_list')->with('error', 'Invalid user.')->send();
        }

        $processed_categories = [];
        $categories = CategoryHelper::getInstance()->getCategories();

        foreach ($categories as $category) {
            $info = UserLimitsHelper::getInfo($user, $category->id);

            $processed_categories[$category->id] = [
                'name' => $category->name,
                'limit' => $info->limit_per,
                'duration' => $info->duration,
                'spent' => UserLimitsHelper::findSpent($user, $category->id, $info),
            ];
        }

        return view('pages.users.view', [
            'user' => $user,
            'can_interact' => auth()->user()->role->canInteract($user->role),
            'transactions' => $user->getTransactions(),
            'activity_transactions' => $user->getActivities(),
            'categories' => $processed_categories,
            'rotations' => $user->rotations,
        ]);
    }

    public function form()
    {
        $user = User::find(request()->route('id'));
        if ($user != null) {
            if ($user->trashed()) {
                return redirect()->route('users_list')->with('error', 'That user has been deleted.')->send();
            }

            if (!auth()->user()->role->canInteract($user->role)) {
                return redirect()->route('users_list')->with('error', 'You cannot interact with that user.')->send();
            }
        }

        $processed_categories = [];
        $categories = CategoryHelper::getInstance()->getCategories()->sortBy('name');

        foreach ($categories as $category) {
            $processed_categories[] = [
                'id' => $category->id,
                'name' => $category->name,
                'info' => $user == null ? [] : UserLimitsHelper::getInfo($user, $category->id),
            ];
        }

        return view('pages.users.form', [
            'user' => $user,
            'available_roles' => auth()->user()->role->getRolesAvailable()->all(),
            'categories' => $processed_categories,
            'rotations' => RotationHelper::getInstance()->getRotations(),
        ]);
    }
}
