<?php

return [
    'mail_verify_email_subject' => 'Verify your email address',
    'gym_mail_private_pending_subject' => 'New private session awaiting approval',
    'gym_mail_private_pending' => ':member requested :name at :time for :people people. Open booking management to approve or decline the request.',
    'gym_mail_trainer_assigned_subject' => 'New teaching assignment',
    'gym_mail_trainer_assigned' => 'You have been assigned to teach :name at :time.',
    'gym_mail_trainer_assigned_room' => 'Room: :room.',
    'gym_mail_schedule_cancelled_subject' => 'Your session has been cancelled',
    'gym_mail_order_paid_subject' => 'Payment confirmed',
    'gym_mail_order_paid' => 'Payment for order :code: :name was successful. Amount: :amount. Payment method: :method. Time: :time.',
    'gym_mail_order_paid_ticket' => 'Ticket code: :code.',
    'gym_mail_order_paid_terms' => 'A copy of your signed terms is attached to this email.',
    'gym_mail_order_paid_admin_subject' => 'New paid order: :code',
    'gym_mail_order_paid_admin' => ':member paid for order :code: :name. Amount: :amount. Payment method: :method. Time: :time.',
    'gym_notify_booking_cancelled' => 'The studio cancelled :name at :time. No session credit will be deducted.',
    'gym_notify_booking_cancelled_redlight' => 'The studio cancelled :name at :time. No session credit will be deducted. The studio will refund your Redlight payment.',
    'gym_notify_therapy_cancelled' => 'The studio cancelled :name at :time. One session credit has been returned to your package.',
];
