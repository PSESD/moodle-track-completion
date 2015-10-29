<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'report/trackcompletion:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'centerdirector' => CAP_ALLOW
        )
    )
);