<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}

\Bitrix\Main\Page\Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/style.css");
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/script.js");
$providerIds = [];
$waybillIds = [];
foreach($arResult['items'] as $key => $item){
  $waybillIds[] = $item['waybillVitamaxVirtual'];
  $providerIds[] = $item['provider'];
}
if(!empty($waybillIds)){
    $waybills = \Store\Waybill::getList(['filter' => ['ID' => $waybillIds],'select' => ['id', 'name'], 'key' => 'ID'])['waybills'];
}
if(!empty($providerIds)){
  $providers = \Store\ProvidersA::getList(['filter' => ['ID' => $providerIds],'select' => ['id', 'name'] , 'key' => 'ID'])['items'];
}
foreach($arResult['items'] as $key => $item){
  $arResult['items'][$key]['provider'] = $providers[$arResult['items'][$key]['provider']]['name'] ?: $arResult['items'][$key]['provider'];
  $arResult['items'][$key]['waybillVitamaxVirtual'] = $waybills[$arResult['items'][$key]['waybillVitamaxVirtual']]['name'] ?: $arResult['items'][$key]['waybillVitamaxVirtual'];
 // $arResult['items'][$key]['loadOneC'] = ($item['loadOneC'] == 'Y') ? 'Да' : 'Нет';
}

?>
<?if(!($arParams['ajax'])){?>
    <div class="ajaxBlock<?= $arParams['class'] ?>">
<?}?>
<div class="mainGridComponent <?= $arParams['class'] ?> width100"
     data-url="<?=$templateFolder?>"
     data-class='<?=$arParams['class'] ?: 'emptyClass'?>'
     data-request='<?= json_encode($arResult['request']) ?>'
     data-params='<?= json_encode($arParams) ?>'
>
    <div class="gridScroll">
    <div class="flex gridLine header width100" style="height: <?=$arParams['style']['height'] ?: '50px'?>">
      <?foreach($arParams['header'] as $code => $prop){?>
          <div
                  class="gridItem gridItem<?= $code ?>"
               style="<?if(!empty($arParams['select'][$code]['width'])){?> width: <?=$arParams['select'][$code]['width']?><?}?>"
          ><?=$prop['name']?></div>
      <?}?>
    </div>

      <? foreach ($arResult['items'] as $item) { ?>
          <div class="flex gridLine width100" style="height: <?=$arParams['style']['height'] ?: '50px'?>">
            <? foreach ($item as $code => $value) { ?>
                <div
                        class="gridItem gridItem<?= $code ?>"
                        style="<?if(!empty($arParams['select'][$code]['width'])){?> width: <?=$arParams['select'][$code]['width']?><?}?>"
                ><?= !is_array($value) ? $value : implode(', ', $value) ?></div>
            <? } ?>
          </div>
      <? } ?>
    </div>
    <div class="pager flex">
        <?foreach($arResult['pager'] as $page){?>
            <div class="pagerItem <?if($page['active']){ echo  'active';}?>" data-page="<?=$page['urlCode']?>"><?=$page['code']?></div>
        <?}?>
    </div>
</div>
<?if(!($arParams['ajax'])){?>
    </div>
<?}?>
