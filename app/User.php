<?php

namespace App;

use App\Model\helpdesk\Agent\Assign_team_agent;
use App\Model\helpdesk\Agent\Teams;
use App\Model\helpdesk\Agent_panel\Organization;
use App\Model\helpdesk\Agent_panel\User_dep;
use App\Model\helpdesk\Agent_panel\User_org;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

/**
 * @property mixed id
 * @property mixed role
 * @property int is_delete
 * @property int active
 * @property int gender
 * @property array|string|null user_name
 * @property string first_name
 * @property null email
 * @property string remember_token
 * @property string profile_pic
 * @property string password
 * @property mixed last_name
 * @property int assign_group
 */
class User extends Model implements AuthenticatableContract, CanResetPasswordContract, AuthenticatableUserContract
{
    use Authenticatable,
        CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_name', 'email', 'password', 'active', 'first_name', 'last_name', 'ban', 'ext', 'mobile', 'profile_pic',
        'phone_number', 'company', 'agent_sign', 'account_type', 'account_status',
        'assign_group', 'primary_dpt', 'agent_tzone', 'daylight_save', 'limit_access',
        'directory_listing', 'vacation_mode', 'role', 'internal_note', 'country_code', 'not_accept_ticket', 'is_delete', 'telegram_id',];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function getProfilePicAttribute($value)
    {
        $info = $this->avatar();
        $pic = null;
        if ($info) {
            $pic = $this->checkArray('avatar', $info);
        }
        if (!$pic && $value) {
            $pic = '';
            $file = asset('uploads/profilepic/'.$value);
            if ($file) {
                $type = pathinfo($file, PATHINFO_EXTENSION);
                $data = file_get_contents($file);
                $pic = 'data:image/'.$type.';base64,'.base64_encode($data);
            }
        }
        if (!$value) {
            $pic = \Gravatar::src($this->attributes['email']);
        }

        return $pic;
    }

    public function avatar()
    {
        $related = 'App\UserAdditionalInfo';
        $foreignKey = 'owner';

        return $this->hasMany($related, $foreignKey)->select('value')->where('key', 'avatar')->first();
    }

    public function getOrganizationRelation()
    {
        $related = "App\Model\helpdesk\Agent_panel\User_org";
        $user_relation = $this->hasMany($related, 'user_id');
        $relation = $user_relation->first();
        if ($relation) {
            $org_id = $relation->org_id;
            $orgs = new \App\Model\helpdesk\Agent_panel\Organization();
            $org = $orgs->where('id', $org_id);

            return $org;
        }
    }

    public function getOrganization()
    {
        $name = '';
        if ($this->getOrganizationRelation()) {
            $org = $this->getOrganizationRelation()->first();
            if ($org) {
                $name = $org->name;
            }
        }

        return $name;
    }

    public function getOrgWithLink()
    {
        $name = '';
        $org = $this->getOrganization();
        if ($org !== '') {
            $orgs = $this->getOrganizationRelation()->first();
            if ($orgs) {
                $id = $orgs->id;
                $name = '<a href='.url('organizations/'.$id).'>'.ucfirst($org).'</a>';
            }
        }

        return $name;
    }

    public function getEmailAttribute($value)
    {
        if (!$value) {
            $value = \Lang::get('lang.not-available');
        }

        return $value;
    }

    public function getExtraInfo($id = '')
    {
        if ($id === '') {
            $id = $this->attributes['id'];
        }
        $info = new UserAdditionalInfo();
        return $info->where('owner', $id)->pluck('value', 'key')->toArray();
    }

    public function checkArray($key, $array)
    {
        $value = '';
        if (is_array($array)) {
            if (array_key_exists($key, $array)) {
                $value = $array[$key];
            }
        }

        return $value;
    }

    public function twitterLink()
    {
        $html = '';
        $info = $this->getExtraInfo();
        $username = $this->checkArray('username', $info);
        if ($username !== '') {
            $html = "<a href='https://twitter.com/".$username."' target='_blank'><i class='fa fa-twitter'> </i> Twitter</a>";
        }

        return $html;
    }

    public function name()
    {
        $first_name = $this->first_name;
        $last_name = $this->last_name;
        $name = $this->user_name;
        if ($first_name !== '' && $first_name !== null) {
            if ($last_name !== '' && $last_name !== null) {
                $name = $first_name.' '.$last_name;
            } else {
                $name = $first_name;
            }
        }

        return $name;
    }

    public function getFullNameAttribute()
    {
        return $this->name();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function organizations() {
        return User_org::query()->where('user_id', '=', $this->id);
    }

    public function departments() {
        return User_dep::query()->where('user_id', '=', $this->id);
    }

    public function teams() {
        return Assign_team_agent::query()->where('agent_id', '=', $this->id);
    }

    public function orgIDs() {
        return $this->organizations()->pluck('org_id')->toArray();
    }

    public function depIDs() {
        return $this->departments()->pluck('dep_id')->toArray();
    }

    public function teamIDs() {
        return $this->teams()->pluck('team_id')->toArray();
    }

    public function updateFromLDAP($l) {
        $uname = strtolower(explode('@', $l['userprincipalname'][0])[0]);
        $fname = $l['givenname'][0];
        $lname = $l['sn'][0];
        $email = $l['mail'][0];
        $group = array_map(function($w){return explode('=', explode(',', $w)[0])[1];}, $l['memberof']);

        $urole = 'user';
        $ufgrp = 2;
        if (in_array('role_support', $group)) {
            $urole = 'agent';
            $ufgrp = 3;
        }
        if (in_array('role_admin', $group) ) {
            $urole = 'admin';
            $ufgrp = 1;
        }

        $this->user_name    = $uname;
        $this->first_name   = $fname;
        $this->last_name    = $lname;
        $this->email        = $email;
        $this->role         = $urole;
        $this->assign_group = $ufgrp;

        $this->save();

        $i_dep = \App\Model\helpdesk\Agent\Department::query()->whereIn('ad_group', $group)->pluck('id')->toArray();
        $i_org = Organization::query()->whereIn('ad_group', $group)->pluck('id')->toArray();
        $i_tem = Teams::query()->whereIn('ad_group', $group)->pluck('id')->toArray();

        $this->storeDepRelation($i_dep);
        $this->storeOrgRelation($i_org);
        $this->storeTeamRelation($i_tem);
    }

    public function storeDepRelation($ids)
    {
        User_dep::query()->where('user_id', $this->id)->delete();
        foreach ($ids as $oid) {
            User_dep::query()->create(['user_id' => $this->id, 'dep_id'  => $oid]);
        }
    }

    public function storeOrgRelation($ids)
    {
        User_org::query()->where('user_id', $this->id)->delete();
        foreach ($ids as $oid) {
            User_org::query()->create(['user_id' => $this->id, 'org_id'  => $oid]);
        }
    }

    public function storeTeamRelation($ids)
    {
        Assign_team_agent::query()->where('agent_id', $this->id)->delete();
        foreach ($ids as $oid) {
            Assign_team_agent::query()->create(['agent_id' => $this->id, 'team_id'  => $oid]);
        }
    }
}
