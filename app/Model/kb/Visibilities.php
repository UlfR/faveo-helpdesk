<?php
namespace App\Model\kb;
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
        $isVisibleDefault = (new VisibilityDefaults)->isVisibleForPart($entity_id, $entity_type, $part_type);
        $vr = self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->whereIn('part_id', $parts);
        return ($isVisibleDefault + $vr->where('is_visible', '=', !$isVisibleDefault)->exists() <= 1);
    }

    public function isVisible($entity_type, $entity_id, $orgs, $deps, $teams)
    {
        $isVisibleO = $this->isVisibleForParts($entity_type, $entity_id, 'org', $orgs);
        $isVisibleD = $this->isVisibleForParts($entity_type, $entity_id, 'dep', $deps);
        $isVisibleT = $this->isVisibleForParts($entity_type, $entity_id, 'team', $teams);

        return $isVisibleO && $isVisibleD && $isVisibleT;
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

