<?php

/// Default values for fields, and Settings used when creating or executing Dropin Script

$scriptSettings = array(
    'standardProfile' => array(

        /// GENERAL SETTINGS
        'loadComponentDefaultsFromTable' => true, // Whether to set unspecified item record values from the pef_component.default_values field
        'existingContainers' => 'delete',      // 'delete' = delete and recreate existing container record with all children.
                                               // 'ignore-all' = ignore and do not create/update container or any of its children (item records are subject to 'existingItems' setting).
                                               // 'ignore' = ignore and do not create/update container
                                               // 'update' = add/update container record with children (don't delete children, except item records are subject to 'existingItems' setting).
                                               // 'match' (***TODO): add/update container record with children (delete missing children, except item records are subject to 'existingItems' setting).
                                               // 'add' = add container with new name
                                               // 'error' = raise error if container exists.
                                                   
        'existingItems' => 'delete',           // 'delete' = delete and recreate existing item records with all their children.
                                               // 'ignore' = ignore and do not create/update item or its children.
                                               // 'ignore-all' = ignore and do not create/update item or any of its children.
                                               // 'update' = add/update item record with children (don't delete children, except item records are subject to 'existingItems' setting).
                                               // 'match' (***TODO): add/update/delete item record with children (delete missing children, except item records are subject to 'existingItems' setting).
                                               // 'error' = raise error if item exists.

        'invalidForeignKeyValues' => 'report', // 'report' = report invalid foreign key values at end of script execution. 
                                               // 'error' => 'raise error when invalid foreign key value is encountered.',

        'matchDibUidValues' => false,  // ***TODO Whether to use dibuid values where available to match existing records. Alternatively unique/all values are used.

        'transactionType' => 'script', // 'script' = one transaction for entire script. Any errors will rollback entire script. 
                                       // 'statement' = one transaction per statement. 
                                       // 'none' = no transactions (not recommended).

        /// ITEM RECORD DEFAULTS SET FOR SPECIFIC COMPONENTS 

        // Override the item record values loaded from the pef_component.default_values field here if needed.
        // NOTE, you can override these values using Dropin Script: 'defaults: componentDefaults.btnDelete.icon=delete, ...'
        'componentDefaults' => array(
            /* Example:
            'btnSave' => array(
                'attributes'=>'mat-mini-fab color="accent"',
                'icon'=>'save'
            )
            */
        ),

        /// TABLE FIELD DEFAULTS

        // The rest of the values below specify defaults for specific table fields. Note, component defaults take precedence.
        // NOTE, you can override these values using Dropin Script: 'defaults: pef_container.pef_dropin_id=DROPIN_ID, ...'

        'pef_container' => array(
            'pef_dropin_id' => 1, // Default Module Dropin for containers.
            'model' => 'Table',
            'cross_db_compat' => 0,
            'filter_syntax' => 'mysql',
            'close_button' => 1,
            'min_button' => 0,
            'max_button' => 0,
            'buffered_grid' => 1,
            'has_filter_boxes' => 1,
            'keep_in_memory' => 0,
            'filter_any_part' => 1,
            'server_timeout' => 180,
        ),

        'pef_container_event' => array(
            'response_type' => 'actions',
            'confirmation_yes_text' => 'Yes',
            'confirmation_cancel_text' => 'Cancel',
            'save_before_action' => 0,
            'wait_for_completion' => 0,
            'order_no' => 10,
        ),

        'pef_container_filter' => array(
            'filter_syntax' => 'mysql',
        ),

        'pef_item' => array(
            'item_alias' => 0,
            'filter_syntax' => 'mysql',
            'subscript_sizing' => 'dynamic',
            'field_appearance' => 'standard',
            'ui_include' => 1,
            'crud_include' => 1,
            'is_required' => 0,
            'hidden' => 0,
            'disabled' => 0,
            'read_only' => 0,
            'limit_to_list' => 'CRUD',
            'nav_item_alias' => 0,
            'enable_ts_vars' => 0,
            'content_type' => 'other',
            'clear_dropdown_on_reload' => 1,
            'order_no' => 10,
            'test_order_no' => 0,
            'test_ignore' => 0,
            'freeze_column' => 0,
            'icon_font_awesome' => 0,
            'reusable_source' => 0,
            'expanded' => 0,
        ),

        'pef_item_event' => array(
            'response_type' => 'actions',
            'disable_until_response' => 1,
            'confirmation_yes_text' => 'Yes',
            'confirmation_cancel_text' => 'Cancel',
            'save_before_action' => 0,
            'wait_for_completion' => 0,
        ),

        'pef_item_filter' => array(
            'filter_syntax' => 'mysql',
        ),

        'pef_item_setting' => array(
            'setting_type' => 'other',
        ),

        'pef_sql' => array(
            'filter_any_part' => 1,
        ),

        'pef_perm_container' => array(
            'criteria_priority' => 0,
            'container_events' => 1,
            'item_events' => 1,
            'open' => 1,
            'read' => 1,
            'create' => 1,
            'update' => 1,
            'delete' => 1,
        ),

        'pef_perm_item' => array(
            'read' => 0,
            'update' => 0,
            'create' => 0,
        ),

        'pef_port' => array(
            'port_alias' => 'main',
            'window_mode' => 'normal',
            'window_state' => 'normal',
            'change_url' => 1,
            'auto_refresh' => 1,
            'keep_history' => 1,
            'remember_query_string' => 1,
            'draggable' => 1,
        ),
    )
);
