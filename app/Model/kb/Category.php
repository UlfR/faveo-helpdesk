<?php
namespace App\Model\kb;
use App\BaseModel;
use App\User;

/**
 * @property mixed id
 * @property mixed parent
 * @property mixed name
 */
class Category extends BaseModel
{
    protected $table = 'kb_category';
    protected $fillable = ['id', 'slug', 'name', 'description', 'status', 'parent', 'created_at', 'updated_at'];

    public function isVisible($orgs, $deps, $teams)
    {
        return (new Visibilities)->isVisible('category', $this->id, $orgs, $deps, $teams);
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
        if (!$user) return false;
        if ($user->role == 'admin') {return true;}
        return $this->isVisible($user->orgIDs(), $user->depIDs(), $user->teamIDs());
    }

    public function desc() {
        if (!empty($this->parentCategory)) {
            return "{$this->parentCategory->name} > {$this->name}";
        } else {
            return $this->name;
        }
    }

    public function parentCategory()
    {
//        return self::query()->where('topic', '=', $this->parent_topic)->first();
        return $this->belongsTo(__CLASS__, 'parent');
    }
}
