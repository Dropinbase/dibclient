<?php

$configs = [
    'sql' => 'SELECT id, date_time as `Date`, username as `User`, `action` as `Action`, perm_group, container_name as `Container`
              FROM pef_activity_log
              ORDER BY date_time
            ',
    'databaseId' => DIB::$ACTIVITYLOGDB,
    'dateField' => 'Date',
    'dateGroups' => [
        '(None)' => 'None',
        'Year'     => '%Y',
        'YearMonth'=> '%Y%m',
        'YearWeek' => '%x%v',
        'Day'      => '%Y%m%d'
    ],
    'groupByFields' => ['User', 'Action', 'Container'],
    'primaryField' => 'id',
    'fromDateDefault' => Date('Y-m-d', strtotime('-3 months')),
    'toDateDefault' => Date('Y-m-d')
];