<?php

namespace App\Models;

use EightyNine\Approvals\Models\ApprovableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends ApprovableModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pr_no',        
        'purpose',
        'for',
        'location',
        'used_fund',
        'expected_date',
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'expected_date' => 'date',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processApprovers(): MorphMany
    {
        return $this->morphMany(ProcessApprover::class, 'modelable');
    }

    public function requestItems(): MorphMany
    {
        return $this->morphMany(RequestItem::class, 'requestitemable');
    }    

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => function(){
                $total = 0;
                foreach($this->requestItems as $item){
                    $total += $item->amount;
                }   
                return $total;
            },
        );
    }

    protected function requested(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user ? $this->user->full_name : $this->createdBy()->full_name,
        );
    }

    protected function approvers(): Attribute
    {
        return Attribute::make(
            get: function() {
                $approvers = collect();
                foreach($this->processApprovers as $approver){
                    if($approver->user_id){
                        $approvers->push($approver->user);
                    }else{
                        foreach(User::role($approver->role_id)->get() as $user){
                            $approvers->push($user);
                        }
                    }
                }

                return $approvers;
            },
        );
    }
}
