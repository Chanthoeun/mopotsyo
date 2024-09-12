<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\LeaveRequest;
use App\Models\OverTime;
use App\Models\User;
use App\Settings\SettingOptions;
use App\Traits\SendNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;

class ProcessApprovalDiscardNotificationListener
{
    use SendNotification;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProcessDiscardedEvent $event): void
    {
        $discarded = $event->approval;
        $approvable = $discarded->approvable;
        if(get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestDiscarded($approvable, $discarded);
        }else if(get_class($approvable) == OverTime::class){
            $this->overtimeDiscarded($approvable, $discarded);
        }
    }

    protected function leaveRequestDiscarded(LeaveRequest $leaveRequest, $discarded){
        $creator = $leaveRequest->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.leave_request')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded', [
                    'request'  => strtolower(__('model.leave_request')), 
                    'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                    'leave_type' => strtolower($leaveRequest->leaveType->name),
                    'from'  => $leaveRequest->from_date->toDateString(), 
                    'to' => $leaveRequest->to_date->toDateString(),
                    'name'  => $discarded->approver_name
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);      
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $receivers = User::whereIn('id', $leaveRequest->processApprovers->pluck('approver_id')->toArray())->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.leave_request')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded', [
                        'request'  => strtolower(__('model.leave_request')), 
                        'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                        'leave_type' => strtolower($leaveRequest->leaveType->name),
                        'from'  => $leaveRequest->from_date->toDateString(), 
                        'to' => $leaveRequest->to_date->toDateString(),
                        'name'  => $discarded->approver_name
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                    ]
                ]);
                
                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $leaveRequest::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
        
    }

    protected function overtimeDiscarded(OverTime $overtime, $discarded){
        $creator = $overtime->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.overtime')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_overtime', [                            
                    'amount'    => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                    'date'      => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
                    'name'      => $discarded->approver_name,     
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $receivers = User::whereIn('id', $overtime->processApprovers->pluck('approver_id')->toArray())->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.overtime')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_overtime', [                            
                        'amount'    => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                        'date'      => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
                        'name'      => $discarded->approver_name,     
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                    ]
                ]);

                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $overtime::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
    }
}
