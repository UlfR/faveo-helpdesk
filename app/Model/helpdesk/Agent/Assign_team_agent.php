<?php

namespace App\Model\helpdesk\Agent;

use App\BaseModel;

class Assign_team_agent extends BaseModel
{
    protected $table = 'team_assign_agent';
    protected $fillable = ['id', 'team_id', 'agent_id', 'updated_at', 'created_at'];

    public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }
}
