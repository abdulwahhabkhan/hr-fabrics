<?php

return [

    /*
    | Days a password stays valid before the user must change it. Null or 0 disables expiry.
    */
    'expiry_days' => env('PASSWORD_EXPIRY_DAYS', 90),

    /*
    | Number of previous passwords that cannot be reused. 0 disables the history check.
    */
    'history_count' => env('PASSWORD_HISTORY_COUNT', 5),

];
