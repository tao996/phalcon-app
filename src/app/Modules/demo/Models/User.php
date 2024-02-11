<?php

namespace app\Modules\demo\Models;

use app\Modules\demo\DemoBaseModel;

class User extends DemoBaseModel
{
    public $id = 0;
    public $title = '';
    public $email = '';

    public function articles()
    {
        return $this->hasManyPhx(Article::class);
    }

    public function profile()
    {
        return $this->hasOnePhx(Profile::class);
    }

    public function roles()
    {
        return $this->hasManyToManyPhx(Role::class,UsersRoles::class);
    }

}