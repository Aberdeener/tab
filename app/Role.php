<?php

namespace App;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Role extends Model implements CastsAttributes
{
    use QueryCacheable;

    protected $cacheFor = 180;

    protected $fillable = ['order'];

    protected $casts = [
        'name' => 'string',
        'superuser' => 'boolean', // if this is true, this group can do anything and edit any group
        'order' => 'integer', // heierarchy system. higher order = higher priority
        'staff' => 'boolean', // determine if they should ever have a password to login with
        'permissions' => 'array' // decode json to an array automatically
    ]; 

    public function get($model, string $key, $value, array $attributes)
    {
        return Role::find($value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value; // TODO: Test for edge cases where this might break (like user class)
    }

    public static function getRoles(string $order = 'DESC'): object
    {
        return Role::orderBy('order', $order)->get();
    }

    public static function getStaffRoles(): array
    {
        return Role::select('id', 'name')->orderBy('order', 'ASC')->where('staff', true)->get()->toArray();
    }

    public function getRolesAvailable(): array
    {
        $roles = array();
        foreach (self::getRoles() as $role) {
            if ($this->canInteract($role)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    public function canInteract(Role $subject): bool
    {
        if ($this->superuser) {
            return true;
        }
        
        if ($subject->superuser) {
            return false;
        }
        
        return $this->order < $subject->order;
    }

    public function hasPermission($permissions): bool
    {
        if ($this->superuser) {
            return true;
        }

        if (!is_array($permissions)) {
            return in_array($permissions, $this->permissions);
        } else {
            foreach ($permissions as $permission) {
                if (!in_array($permission, $this->permissions)) {
                    return false;
                }
            }
            
            return true;
        }
    }
}