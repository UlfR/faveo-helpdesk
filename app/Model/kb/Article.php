<?php

namespace App\Model\kb;

use App\BaseModel;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class Article extends BaseModel
{
    use SearchableTrait;

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        'columns' => [
            'name'        => 10,
            'slug'        => 10,
            'description' => 10,
        ],
    ];

    /*  define the table name to get the properties of article model as protected  */
    protected $table = 'kb_article';
    /* define the fillable field in the table */
    protected $fillable = ['id', 'name', 'slug', 'description', 'type', 'status', 'publish_time'];

    public function isVisible($orgs, $deps, $teams)
    {
        return (new Visibilities)->isVisible('article', $this->id, $orgs, $deps, $teams);
    }

    public function isVisibleForUserID($user_id)
    {
        $user = User::query()->find($user_id);
        return !empty($user) ? $this->isVisibleForUser($user->first()) : false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isVisibleForUser($user)
    {
        if ($user->role == 'admin') {return true;}
        return $this->isVisible($user->orgIDs(), $user->depIDs(), $user->teamIDs());
    }
}
