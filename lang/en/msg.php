<?php

return [
    // title
    'already_exist'     => 'Already exist',  
    'added'             => 'Added',
    'created'           => 'Created',
    'updated'           => 'Updated',
    'resigned'          => 'Resigned',
    'break_time'        => 'Break Time 1 = 1h or 1.5 = 1h : 30min',
    'failed'            => 'Failed!',
    'imported'          => 'Imported',
    'in_progress'       => 'In Progress',
    'success'           => 'Success',
    'balance_is_not_enough' => 'Balance is not enough.',
    'approved'          => 'Approved',
    'rejected'          => 'Rejected',
    'discarded'         => 'Discarded',
    'requested'         => 'Requested',
    'submitted'         => 'Submitted',
    'completed'         => 'Completed',
    'sent'              => 'Sent',

    // label
    'label' => [
        'added'         => ':label Added',
        'created'       => ':label Created',
        'failed'        => ':label Failed!',
        'imported'      => ':label Imported!',
        'in_progress'   => ':label In Progress',
        'success'       => ':label Success',
        'approved'      => ':label Approved',
        'rejected'      => ':label Rejected',
        'discarded'     => ':label Discarded',
        'submitted'     => ':label Submitted',
        'completed'     => ':label Completed',
        'sent'          => ':label Sent',
    ],

    // message
    'body'   => [
        'already_exist'       => ':name is already exist.',
        'added'               => ':name was added',  
        'created'             => ':name was created.',
        'updated'             => ':name has been updated.',
        'resigned'            => ':name is set.',
        'sent'                => ':name has been sent successfully.',
        'imported'            => ':name was imported successfully with :count records!',
        'failed'              => ':name was :action failed, please try again!',
        'in_progress'         => 'We are :name :count records. we will notify you when it is done.',
        'success'             => ':name was :action successfully.',
        'in_advance'          => 'Your request must be submitted :days day in advance.|Your request must be submitted :days days in advance.',
        'submit_leave_request' => ':name would like to request :days of :leave_type from :from to :to. Would you like to approve this request?',
        'approved_leave_request' => ':name just requested :days of :leave_type from :from to :to so it needs your approval to process next step. Would you like to approve this request?',
        'rejected'             => 'The :request for :days of :leave_type from :from to :to was rejected by :name.',
        'discarded'            => 'The :request for :days of :leave_type from :from to :to was discarded by :name.',
        'completed_leave_request'   => 'Your :request for :days of :leave_type from :from to :to was approved and completed.',
        'request_over_accrued_amount'      => 'Your request date is over accrued amount. Your current accured amount is :amount.',
        'is_working_hour'   => 'The request date and time is in working hour. Please choose another date and time.',
        'overtime'          => ':name just :action an overtime for :amount on :date. please kindly check and approve in the below link.',
        'completed_overtime'    => 'Your overtime request for :amount on :date was approved and completed.',
        'rejected_overtime'     => 'Your overtime request for :amount on :date was rejected by :name.',
        'discarded_overtime'    => 'The overtime request for :amount on :date was discarded by :name.',
        'is_not_correct'    => 'The informationis not correct.',
        
    ]
];
