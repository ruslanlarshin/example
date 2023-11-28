<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
global $APPLICATION;
$params = json_decode($_REQUEST['params'], true);
$params['nav']['page'] = $_REQUEST['page'];
$params['ajax'] = true;
$APPLICATION->IncludeComponent(
  'main:grid',
  '',
  $params,
);
?>