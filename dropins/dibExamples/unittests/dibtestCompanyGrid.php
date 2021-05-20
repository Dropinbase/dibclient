<?php 

// dibtestCompanyGrid
/*
$params = array('action'=>'xlsx', 'actionType'=>'itemAlias',  'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null); // item where container is injected
ClientFunctions::addAction($actionList, 'run', $params);

$params = array('action'=>'xlsxA', 'actionType'=>'itemAlias',  'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params);
*/


$params = array('action'=>'btnAdd', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params);

/*
$params = array('action'=>'addRecord', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'ContainerActions', $params);
*/

/*
$params = array('action'=>'btnEdit', 'actionType'=>'itemAlias',  'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params);
*/

// dibtestCompanyForm
$containerName = 'dibtestCompanyForm';

// TODO -> new actions: set-model-value   AND  set-list-value
$params = array('name'=>substr(uniqid(), 9, 5), 'chinese_name'=>substr(uniqid(), 9, 5), 'parent_company_id'=>1);
ClientFunctions::addAction($actionList, 'set-value', $params);

$params = array('action'=>'btnsave', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>$containerName, 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params);






