<?php

namespace App\Model\helpdesk\Ticket;

use App\BaseModel;
use App\Model\helpdesk\Manage\Sla_plan;
use App\Model\helpdesk\Utility\Priority;
use Exception;
use Log;
use App\User;
use App\Model\helpdesk\Manage\Help_topic;

/**
 * @property mixed id
 * @property mixed type_id
 * @property mixed priority
 * @property mixed thread
 * @property mixed user
 * @property mixed helptopic
 * @property mixed ticket_number
 * @property \DateTime|false duedate
 * @property mixed created_at
 * @property mixed status
 * @property mixed sla
 * @property type assigned_to
 * @property type priority_id
 * @property type source
 * @property type help_topic_id
 * @property type user_id
 * @property type dept_id
 */
class Tickets extends BaseModel
{
    public const TYPES = ['question', 'issue', 'feature'];
    protected $table = 'tickets';
    protected $fillable = ['id', 'ticket_number', 'num_sequence', 'user_id', 'priority_id', 'sla', 'help_topic_id', 'max_open_ticket', 'captcha', 'status', 'lock_by', 'lock_at', 'source', 'isoverdue', 'reopened', 'isanswered', 'is_deleted', 'closed', 'is_transfer', 'transfer_at', 'reopened_at', 'duedate', 'closed_at', 'last_message_at', 'last_response_at', 'created_at', 'updated_at', 'assigned_to', 'type_id'];

//        public function attach(){
//            return $this->hasMany('App\Model\helpdesk\Ticket\Ticket_attachments',);
//
//        }
    public function thread()
    {
        return $this->hasMany(Ticket_Thread::class, 'ticket_id');
    }

    public function collaborator()
    {
        return $this->hasMany(Ticket_Collaborator::class, 'ticket_id');
    }

    public function helptopic()
    {
        $related = Help_topic::class;
        $foreignKey = 'help_topic_id';

        return $this->belongsTo($related, $foreignKey);
    }

    public function formdata()
    {
        return $this->hasMany(Ticket_Form_Data::class, 'ticket_id');
    }

    public function extraFields()
    {
        $id = $this->attributes['id'];
        $ticket_form_datas = Ticket_Form_Data::query()->where('ticket_id', '=', $id)->get();

        return $ticket_form_datas;
    }

    public function source()
    {
        $source_id = $this->attributes['source'];
        return Ticket_source::query()->find($source_id);
    }

    public function sourceCss()
    {
        $css = 'fa fa-comment';
        $source = $this->source();
        if ($source) {
            $css = $source->css_class;
        }

        return $css;
    }

    public function delete()
    {
        $this->thread()->delete();
        $this->collaborator()->delete();
        $this->formdata()->delete();
        parent::delete();
    }

    public function setAssignedToAttribute($value)
    {
        if (!$value) {
            $this->attributes['assigned_to'] = null;
        } else {
            $this->attributes['assigned_to'] = $value;
        }
    }

    public function getAssignedTo()
    {
        return User::query()->find($this->assigned_to);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function status()
    {
        return $this->belongsTo(Ticket_Status::class, 'status')->first();
    }

    public function sla()
    {
        return $this->belongsTo(Sla_plan::class, 'sla')->first();
    }

    public function type()
    {
        return self::TYPES[$this->attributes['type_id']];
    }

    public function getTelegram()
    {
        $chat_ids = [];
        $team_ids = $this->user ? $this->user->teamIDs() : [];

        if (in_array(1, $team_ids)) $chat_ids[] = '-322027375'; //1level
        if (in_array(4, $team_ids)) $chat_ids[] = '-390912367'; //2level
        if (count($chat_ids) == 0) {
            $chat_ids[] = '-322027375'; //1level
            //$chat_ids[]= '265102183'; //admin
        }

        return $chat_ids;
    }

    public function getMessageInfo()
    {
        $user      = $this->user;
        $email     = $user->email;
        $name      = implode(' ', [$user->first_name, $user->last_name]);
        $deps      = implode(', ',
            $user->organizations()->get()->map(function($r){return $r->organization->name;})->toArray()
        );
        $mods      = implode(', ',
            $user->departments()->get()->map(function($r){return $r->department->name;})->toArray()
        );
        $teams     = implode(', ',
            $user->teams()->get()->map(function($r){return $r->team->name;})->toArray()
        );

        $type      = $this->type();
        $status    = $this->status()->name;
        $priority  = $this->priority->priority;

        $thread    = $this->thread->first();
        $subject   = $thread ? $thread->title : '';
        $body      = $thread ? $thread->body : '';

        $helptopic = $this->helptopic;
        $topics    = implode(' > ', [$helptopic->parent_topic, $helptopic->topic]);

        $sla       = $this->sla();
        $sla       = $sla ? $sla->grace_period : '-';

        return <<<ZZZ
User: {$name}({$email})
Departments: {$deps}
Modules: {$mods}
Teams: {$teams}
Priority: {$priority}
SLA: {$sla}
Type: {$type}
Status: {$status}
Topics: {$topics}
Subject: {$subject}
-----
{$body}
ZZZ;
    }

    public function sendToTelegram($current_user, $type, $message=null)
    {
        $ticket_link = route('ticket.thread', [$this->id]);
        $head        = "#{$this->ticket_number}({$ticket_link})";
        $message     = $message ?: $this->getMessageInfo();
        $agent       = $this->getAssignedTo();
        $chat_ids    = $agent ? [$agent->telegram] : $this->getTelegram();
        $msg_type    = 'message';

        if ($agent && $agent->id == $current_user->id) return $this;
        if ($type == 'create') $msg_type = 'created';
        if ($type == 'update') $msg_type = 'updated';

        $text        = <<<ZZZ
Ticket {$msg_type}
{$head}‎
#####
{$message}
#####
ZZZ;

        try {
            Log::info('sendToTelegram: ' . $text . ' to ' . implode(', ', $chat_ids));
            foreach ($chat_ids as $chat_id) \Telegram::sendMessage(['chat_id' => $chat_id, 'text' => $text]);
        } catch (Exception $e) {
            Log::info('sendToTelegram failed: ' . $e->getMessage());
        }

        return $this;
    }

    #TODO send sms
    public function sendToSMS($params)
    {
    }

}
