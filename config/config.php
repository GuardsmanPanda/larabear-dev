<?php

return [
    //------------------------------------------------------------------------------------------------------------------
    // Config for generating eloquent models, the "eloquent-models" array has en entry for each connection that wants models generated,as defined in config/database.php
    //------------------------------------------------------------------------------------------------------------------
    'eloquent-model-generator' => [
        'pgsql' => [
            'users' => [
                'class' => 'User',
                'audit_exclude_columns' => ['microsoft_last_login_at'],
                'location' => 'Domain/User/Model',
                'traits' =>[],
            ],
        ]
    ]
];