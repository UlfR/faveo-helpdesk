<?php
namespace App\Model\kb;
use Illuminate\Database\Eloquent\Model;

class VisibilityDefaults extends Model
{
    protected $table = 'kb_entity_visibility_defaults';
    protected $fillable = ['id', 'entity_id', 'entity_type', 'part_type', 'is_visible', 'updated_at', 'created_at'];

    public function isVisibleForPart($entity_type, $entity_id, $part_type)
    {
        $vdr = self::query()
            ->where('entity_type', '=', $entity_type)
            ->where('entity_id', '=', $entity_id)
            ->where('part_type', '=', $part_type)
            ->first();

        if ($vdr) { return $vdr->is_visible; }
        return null;
    }

    public function setVisibility($entity_type, $entity_id, $part_type, $is_visible)
    {
        self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->delete();

        if ($is_visible === null || $is_visible == -1) { return null; }
        return self::query()->create([
            'entity_id' => $entity_id,
            'entity_type' => $entity_type,
            'part_type' => $part_type,
            'is_visible' => $is_visible,
        ]);
    }

    public function getVisibility($entity_type, $entity_id, $part_type)
    {
        $v = self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->where('part_type', '=', $part_type)
            ->first();
        return $v ? $v->is_visible : $v;
    }

    public function setVisibilities($entity_type, $entity_id, $parts_info)
    {
        self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->delete();

        foreach ($parts_info as $part_type => $is_visible) {
            if ($is_visible === null || $is_visible == -1) { continue; }
            self::query()->create([
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'part_type' => $part_type,
                'is_visible' => $is_visible,
            ]);
        }
    }

    public function getVisibilities($entity_type, $entity_id)
    {
        return self::query()
            ->where('entity_id', '=', $entity_id)
            ->where('entity_type', '=', $entity_type)
            ->pluck('is_visible', 'part_type')->toArray();
    }
}
