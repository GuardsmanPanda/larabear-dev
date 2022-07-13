<?php

return [
    //------------------------------------------------------------------------------------------------------------------
    // General config required to generate Crud classes.
    //------------------------------------------------------------------------------------------------------------------
    'data_access_layer_folder' => 'Domain',
    'application_layer_folder' => 'Service',
    'presentation_layer_folder' => 'Web',

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
