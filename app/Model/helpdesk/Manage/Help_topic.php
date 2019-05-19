<?php

namespace App\Model\helpdesk\Manage;

use App\BaseModel;
use App\Model\helpdesk\Agent\Department;

/**
 * @property mixed parent_topic
 * @property mixed topic
 * @property Help_topic parent
 * @property mixed children
 */
class Help_topic extends BaseModel
{
    protected $table = 'help_topic';
    protected $fillable = [
        'id', 'topic', 'parent_topic', 'custom_form', 'department', 'ticket_status', 'priority',
        'sla_plan', 'thank_page', 'ticket_num_format', 'internal_notes', 'status', 'type', 'auto_assign',
        'auto_response',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department');
    }

    public function children() {
//        return self::where('status', '=', 1)->where('parent_topic', '=', $this->topic)->get();
        return $this->hasMany(__CLASS__, 'parent_topic');
    }

    public function desc() {
        if (!empty($this->parent_topic)) {
            return "{$this->parent->topic} > {$this->topic}";
        } else {
            return $this->topic;
        }
    }

    public function parent()
    {
//        return self::query()->where('topic', '=', $this->parent_topic)->first();
        return $this->belongsTo(__CLASS__, 'parent_topic');
    }

    public function parentName() {
        return $this->parent ? $this->parent->topic : null;
    }

    public static function actives() {
        return self::query()->where('status', '=', 1);
    }

    public static function activesHash() {
        return self::activesObject()->map(function($x){return [$x->id, $x->desc()];})->reduce(function($a, $v){$a[$v[0]] = $v[1]; return $a;});
    }

    public static function activesObject() {
        return self::actives()->get()->sort(function($a, $b){ return $a->desc() <=> $b->desc();});
    }
}
