<?php 

// dibtestCompanyGrid
/*
$params = array('action'=>'xlsx', 'actionType'=>'itemAlias',  'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null); // item where container is injected
ClientFunctions::addAction($actionList, 'run', $params);
$params = array('action'=>'xlsxA', 'actionType'=>'itemAlias',  'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params);
*/

// $params = array('action'=>'btnAdd', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
// ClientFunctions::addAction($actionList, 'run', $params);

/*
$params = array('action'=>'addRecord', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'ContainerActions', $params);
*/

/*
$params = array('action'=>'btnEdit', 'actionType'=>'itemAlias',  'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params);
*/
$params = array('key'=> 'unitTesting', 'value' => true);
ClientFunctions::addAction($actionList, 'set-environment-value', $params);

// dibtestCompanyForm
$containerName = 'dibtestCompanyForm';

// TODO -> new actions: set-model-value AND set-list-value
$params = array('parent_company_id'=> 'Global');
ClientFunctions::addAction($actionList, 'set-value-list', $params);
//sleep(5);
// $params = array('action'=>'btnsave', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
// ClientFunctions::addAction($actionList, 'run', $params);

$params = array('defaultValue'=> 'Hello', 'close' => true);
ClientFunctions::addAction($actionList, 'set-prompt-value', $params);

