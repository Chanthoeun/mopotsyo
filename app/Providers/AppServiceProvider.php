<?php

namespace App\Providers;

use App\Listeners\ApprovalLeaveRequestNotificationListener;
use App\Listeners\SubmittedLeaveRequestNotificationListener;
use App\Models\LeaveCarryForward;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\AuthenticationLogPolicy;
use App\Policies\LeaveCarryForwardPolicy;
use App\Policies\LeaveEntitlementPolicy;
use App\Policies\LeaveRequestPolicy;
use App\Policies\ProcessApprovalFlowPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use RingleSoft\LaravelProcessApproval\Events\ApprovalNotificationEvent;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlow;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(AuthenticationLog::class, AuthenticationLogPolicy::class);
        Gate::policy(ProcessApprovalFlow::class, ProcessApprovalFlowPolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(LeaveEntitlement::class, LeaveEntitlementPolicy::class);
        Gate::policy(LeaveCarryForward::class, LeaveCarryForwardPolicy::class);

        
        Gate::define('use-translation-manager', function (?User $user) {
            // Your authorization logic
            return $user !== null && $user->hasRole('super_admin');
        });


        // Events
        // Event::listen(ApprovalNotificationEvent::class, ApprovalLeaveRequestNotificationListener::class);
    }
}
