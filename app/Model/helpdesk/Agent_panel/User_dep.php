<?php

namespace App\Model\helpdesk\Agent_panel;

use App\BaseModel;
use App\Model\helpdesk\Agent\Department;

class User_dep extends BaseModel
{
    protected $table = 'user_assign_department';
    protected $fillable = ['id', 'dep_id', 'user_id'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'dep_id');
    }
}
