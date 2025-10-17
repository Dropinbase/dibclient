<?php 

/* Configuration file for bulk generating UI(client-side) and CRUD(server-side) code from the command line.
   - Populate the $clientData array below as you would the /nav/dibRelease values
   - To execute:
     This file must be in your project's /configs folder
     cd /var/www/html/myProject/dropinbase/dropins/dibAdmin/components
     php DDropinFilesRelease.php /var/www/html/myProject/configs/ReleaseConfigs.php
*/

$callDibUserParamsAsUser = 'dib_david'; // This must be a developer/superuser (x1x).
$callDibUserParams = true; // Whether DibUserParams::getUserParams() must be called to populate DIB::$USER values for the user.

$clientData = [
    'alias_self' => [
        'cache' => 1, // Whether to generate UI/Cache files, where 1=true and 0=false
        'crud' => 1, // Whether to generate Serverside CRUD files, where 1=true and 0=false
        'perm' => 1, // Allways set to 1 (whether to generate permission records)
        'actions' => 1, // Whether to generate actions
        'components' => 1, // Whether to generate components
        'skipExisting' => 0, // Whether to skip generating files that already exist
        'maxThreads'=> 6, // Maximum number of asynchronous threads to use
        'timeoutMin' => '300', // PHP timeout in minutes for the main thread, as well as each of the asynchronous threads.

        'permGroup' => -1, // Choose permGroup, eg 'x1x' or -1, where -1 = All.
        
        'dropinIds'=>[
            ['id' => 1],
            ['id' => 2],
        ], // Id values of module dropins to include, eg null (for all) or [['id'=>1], ['id'=>2]] for dropin IDs 1 and 2

        'displayDropinIds' => null, // Id values of display dropins to include, eg null (for all) or [['id'=>16], ['id'=>17]] for dropin IDs 16 and 17 (corresponding to grids and forms by default)

        'containerId' => null, // Id values of specific containers to generate, eg null (for all) or [['id'=>1], ['id'=>2]] for container's with id values 1 and 2

        'criteria' => '', // Any SQL criteria to be used to futher limit the selection of containers to generate (see FROM clause below for table aliases to use in SQL criteria)
        
    ]
];

/* Info: FROM clause used in the SQL which selects the containers to generate (see 'criteria' above):

FROM (pef_container c 
        INNER JOIN pef_dropin displaydropin ON c.pef_display_dropin_id = displaydropin.id)
    LEFT JOIN pef_dropin moduledropin ON c.pef_dropin_id = moduledropin.id
    LEFT JOIN pef_dropin moduleParentDropin ON moduledropin.pef_parent_dropin_id = moduleParentDropin.id
    LEFT JOIN pef_dropin parentDisplaydropin ON displaydropin.pef_parent_dropin_id = parentDisplaydropin.id
    LEFT JOIN pef_dropin materialdropin ON displaydropin.pef_material_dropin_id = materialdropin.id
    LEFT JOIN pef_perm_active prt ON c.id = prt.pef_container_id
    LEFT JOIN pef_table t ON c.pef_table_id = t.id
    LEFT JOIN pef_sql q ON c.pef_sql_id = q.id

*/