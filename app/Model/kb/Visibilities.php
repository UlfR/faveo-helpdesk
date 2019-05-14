<?php
namespace App\Model\kb;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Visibilities extends Model
{
    protected $table = 'kb_entity_visibility';
    protected $fillable = ['id', 'entity_id', 'entity_type', 'part_id', 'part_type', 'is_visible', 'updated_at', 'created_at'];

    public function isVisibleForPart($entity_type, $entity_id, $part_type, $part_id)
    {
        $vr = self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->where('part_id', '=', $part_id)
            ->orderBy('is_visible')
            ->first();
        return ($vr ? $vr->isVisible : (new VisibilityDefaults)->isVisibleForPart($entity_id, $entity_type, $part_type));
    }

    public function isVisibleForParts($entity_type, $entity_id, $part_type, $parts)
    {
        $isVisibleDefault = (new VisibilityDefaults)->isVisibleForPart($entity_type, $entity_id, $part_type);

        if ($isVisibleDefault === null) {
            if ($entity_type == 'article') {
                $r = Relationship::query()->where('article_id', '=', $entity_id)->first();
                $parent_id = $r ? $r->category_id : 0;
                $isVisibleDefault = $parent_id > 0 ? $this->isVisibleForParts(
                    'category', $parent_id, $part_type, $parts
                ) : null;
            }

            if ($entity_type == 'category') {
                $r = Category::query()->find($entity_id);
                /** @noinspection PhpUndefinedFieldInspection */
                $parent_id = $r ? $r->parent : 0;
                $isVisibleDefault = $parent_id > 0 ? $this->isVisibleForParts(
                    'category', $parent_id, $part_type, $parts
                ) : null;
            }
        }


        $vr = self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->whereIn('part_id', $parts);
        $isNegRowsPresent = $vr->where('is_visible', '=', (int)!$isVisibleDefault)->exists();
        return ($isVisibleDefault + $isNegRowsPresent == 1);
    }

    public function isVisible($entity_type, $entity_id, $orgs, $deps, $teams)
    {
        $isVisibleO = $this->isVisibleForParts($entity_type, $entity_id, 'org', $orgs);
        $isVisibleD = $this->isVisibleForParts($entity_type, $entity_id, 'dep', $deps);
        $isVisibleT = $this->isVisibleForParts($entity_type, $entity_id, 'team', $teams);

        return $isVisibleO && $isVisibleD && $isVisibleT;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isVisibleForUser($entity_type, $entity_id, $user) {
        if ($user->role == 'admin') {return true;}
        return $this->isVisible($entity_type, $entity_id, $user->orgIDs(), $user->depIDs(), $user->teamIDs());
    }

    public function isVisibleForUserID($entity_type, $entity_id, $user_id) {
        $user = User::query()->find($user_id);
        return !empty($user) ? $this->isVisibleForUser($entity_type, $entity_id, $user->first()) : false;
    }

    public function setVisibility($entity_type, $entity_id, $part_type, $part_id, $is_visible)
    {
        self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->where('part_id', '=', $part_id)
            ->delete();
        return self::query()->create([
            'entity_id' => $entity_id,
            'entity_type' => $entity_type,
            'part_type' => $part_type,
            'part_id' => $part_id,
            'is_visible' => $is_visible,
        ]);
    }

    public function getVisibility($entity_type, $entity_id, $part_type, $part_id)
    {
        $v = self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->where('part_id', '=', $part_id)
            ->first();
        return $v ? $v->is_visible : $v;
    }

    public function setVisibilities($entity_type, $entity_id, $part_type, $parts_info)
    {
        self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->delete();

        foreach ($parts_info as $part_id => $is_visible) {
            self::query()->create([
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'part_type' => $part_type,
                'part_id' => $part_id,
                'is_visible' => $is_visible,
            ]);
        }
    }

    public function getVisibilities($entity_type, $entity_id, $part_type)
    {
        return self::query()
            ->where('entity_type', '=', $entity_type)
            ->where('entity_id', '=', $entity_id)
            ->where('part_type', '=', $part_type)
            ->pluck('is_visible', 'part_id')->toArray();
    }
}

