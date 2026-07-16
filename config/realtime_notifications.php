<?php

return [
    'pickup_reminder_minutes' => (int) env('PICKUP_REMINDER_MINUTES', 10),
    'pickup_reminder_grace_minutes' => (int) env('PICKUP_REMINDER_GRACE_MINUTES', 15),
];
