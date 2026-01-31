<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $record;
    public $action;
    public $comment;
    public $actor;

    /**
     * Create a new event instance.
     *
     * @param Model $record The approvable record
     * @param string $action The action performed (submitted, approved, rejected, discarded)
     * @param string|null $comment Optional comment
     * @param \App\Models\User|null $actor The user who performed the action
     */
    public function __construct(Model $record, string $action, ?string $comment = null, ?\App\Models\User $actor = null)
    {
        $this->record = $record;
        $this->action = $action;
        $this->comment = $comment;
        $this->actor = $actor;
    }
}
