<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}

//Компонент принимает название класса дочернего от \Main\IblockA(className)
class MainGrid extends CBitrixComponent
{
  public function executeComponent()
  {
    try {
      $this->getResult();
    } catch (\Bitrix\Main\SystemException $e) {
      ShowError($e->getMessage());
    }
  }

  protected function getResult()
  {
      if(empty($this -> arParams['className'])){//название класса \Store\Waybill
        view('Необходим параметр className ');
        return ['Необходим параметр className '];
      }
      $buf =[];
      if(!empty($this -> arParams['select'])){
        foreach($this -> arParams['select'] as $key => $select){
          $buf[$select['code']] = $select;
        }
        $this -> arParams['select'] = $buf;
      }
      $filter = $this -> arParams['filterDop'] ?: [];
      if(!empty($this -> arParams['search'])){
        if(empty($this -> arParams['searchItems'])){
          $filter['or'] = [
            'id' => '%' . $this -> arParams['search'] . '%',
            'name' => '%' . $this -> arParams['search'] . '%',
            'code' =>  '%' . $this -> arParams['search'] . '%' ,
          ];
        }else{
          foreach($this -> arParams['searchItems'] as $code){
            $filter['or'][] = [$code => '%' . $this -> arParams['search'] . '%',];
          }
        }
      }
      $this -> arResult['select'] = [];
      $this -> arParams['header'] = $this -> arParams['className']::getPropertiesName();

      if(!empty($this -> arParams['select'])){
        foreach($this -> arParams['select'] as $code => $item){
          $this -> arResult['select'][] = $item['code'];
          if(empty( $this -> arParams['header'][$code])){
            $this -> arParams['header'][$code] = $item;
          }
        }
        foreach($this -> arParams['header'] as $code => $item){
          if(!in_array($code, $this -> arResult['select'] ?: [])){
            unset($this -> arParams['header'][$code]);
          }else{
            foreach($this -> arParams['select'] as $select){
              if(!empty($select['name']) && ($code == $select['code'])){
                $this -> arParams['header'][$code]['name'] = $select['name'];
              }
            }
          }
        }
      }
    $this -> arResult['request'] =  [
        'filter' => $filter,
        'order' => $this -> arParams['order'],
        'select' => $this -> arResult['select'],
        'nav' => [
          'limit' => $this -> arParams['nav']['limit'] ?: 0,
          'offset' => (intval(($this -> arParams['nav']['page']) ?: 1) - 1 ) * $this -> arParams['nav']['limit'],
        ],
      ];
    $this -> arResult['items'] = $this -> arParams['className']::getList($this -> arResult['request'])[$this -> arParams['listItems'] ?: 'items'];
    foreach( $this -> arResult['items']  as $key => $item){
      foreach($this -> arParams['select']  as $keySelect => $itemSelect){
        if(!isset($item[$keySelect])){
          $html = $itemSelect['html'];
          foreach($item as $keyItem => $itemValue){
            $html = str_replace('#' . $keyItem . '#', $itemValue, $html);
          }
          $this -> arResult['items'][$key][$keySelect] = $html;
        }
      }
    }
    if(!empty($this -> arParams['nav']['limit'])){
      $count = count($this -> arParams['className']::getList(['select' => ['id'], 'filter' => $filter])[$this -> arParams['listItems'] ?: 'items'] ?: []);
      $count = ceil($count/intval($this -> arParams['nav']['limit'] ?: 1));
      $this -> arResult['pager'] = $this -> arParams['className']::getPager($count, intval($this -> arParams['nav']['page']))['pagers'];
    }
      $this->IncludeComponentTemplate();
  }
}
