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
    'submitted'         => 'Submitted',
    'completed'         => 'Completed',

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
    ],

    // message
    'body'   => [
        'already_exist'       => ':name is already exist.',
        'added'               => ':name was added',  
        'created'             => ':name was created.',
        'updated'             => ':name has been updated.',
        'resigned'            => ':name is set.',
        'imported'            => ':name was imported successfully with :count records!',
        'failed'              => ':name was :action failed, please try again!',
        'in_progress'         => 'We are :name :count records. we will notify you when it is done.',
        'success'             => ':name was :action successfully.',
        'in_advance'          => 'Your request must be submitted :days days in advance.',
        'submit_leave_request' => ':name would like to request :days of :leave_type from :from to :to. Would you like to approve this request?',
        'approved_leave_request' => ':name just requested :days of :leave_type from :from to :to so it needs your approval to process next step. Would you like to approve this request?',
        'rejected'             => 'Your :request for :days of :leave_type from :from to :to was rejected by :name.',
        'discarded'            => 'Your :request for :days of :leave_type from :from to :to was discarded by :name.',
        'completed_leave_request'   => 'Your :request for :days of :leave_type from :from to :to was approved and completed.',
        'request_over_accrued_amount'      => 'Your request date is over accrued amount. Your current accured amount is :amount.',
    ]
];
