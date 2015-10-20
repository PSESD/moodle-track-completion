<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'report/groupcertificatecompletion:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            //'centerdirector' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:config',
    )
);
