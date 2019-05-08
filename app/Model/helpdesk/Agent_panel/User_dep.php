<?php

namespace App\Model\helpdesk\Agent_panel;

use App\BaseModel;

class User_dep extends BaseModel
{
    protected $table = 'user_assign_department';
    protected $fillable = ['id', 'dep_id', 'user_id'];
}
