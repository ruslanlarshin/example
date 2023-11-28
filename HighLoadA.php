<?
namespace Main;

\CModule::IncludeModule('iblock');
\Bitrix\Main\Loader::includeModule("highloadblock");

abstract class HighLoadA
{

  abstract static protected function map();
  abstract static protected function highLoadTableName();

  public static function getHighLoadId($highLoadName = false){
    try{
      return \Bitrix\Highloadblock\HighloadBlockTable::getList(
        [
          'filter' => ['TABLE_NAME' => $highLoadName ?: static::highLoadTableName()],
          'cache' => ['ttl' => \Main\Cache::cacheTime],
        ])-> fetchAll()[0]['ID'] ?: 0;
    }catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function init($highLoadName = false){
    $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(static::getHighLoadId($highLoadName))->fetch();
    $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
    return $entity->getDataClass();
  }

  public static function getList($request = [], $highLoadName = false, $map = false)
  {
    try {
      $highLoadName = $highLoadName ?: static::highLoadTableName();
      $map = $map ?: static::map();
      if($request['cacheTime']){
        $cacheTime = $request['cacheTime'];
        unset($request['cacheTime']);
        $result = \Main\Cache::tagged('\Main\HighLoadA::getList', $highLoadName, $map,  $request, $request, $cacheTime)['items'];
        return ['items' => $result];
      }
      $select = array_values($map);
      if(!empty($request['select'])){
        $select = [];
        foreach($request['select'] as $code){
          $select[] = $map[$code] ?? $code;
        }
        $select = $select ?: ['ID'];
      }
      if(!empty($request['filter'])){
        foreach($request['filter'] as $code => $value){
          if($code === 'or' || $code === 'OR'){
            $logic = ['LOGIC' => 'OR'];
            foreach($value as $codeOr => $filterOr){
              if(is_int($codeOr)) {
                $buf = [];
                foreach ($filterOr as $codeOrItem => $filterOrItem) {
                  if(!empty($map[$codeOrItem])){
                    $buf[$map[$codeOrItem]] = $filterOrItem;
                  }else{
                    $buf[$codeOr] = $filterOrItem;
                  }
                }
                $logic[] = $buf;
              }else{
                $buf = [];
                if(!empty($map[$codeOrItem])){
                  $buf[$map[$codeOrItem]] = $value;
                }else{
                  $buf[$codeOr] = $filterOr;
                }
                $logic[] = $buf;
              }
            }
            $request['filter'][] = $logic;
            unset($request['filter'][$code]);
          }
          if(!empty($map[$code])){
            $request['filter'][$map[$code]] = $value;
            unset($request['filter'][$code]);
          }
        }
      }
      $rsData = self::init($highLoadName)::getList([
        'order' => $request['order'] ?? $request['sort'] ?? [],
        "select" => $select,
        "filter" => $request['filter'] ?? [],
        'limit' => $request['nav']['limit'] ?? $request['limit'] ?? 0,
        'offset' => $request['nav']['offset'] ??  $request['offset'] ?? 0,
      ]);

      if(!empty($request['key']) && !in_array($request['key'] ?: 'none', $request['select'] ?: [])){
        $request['select'][] = $request['key'];
      }
      if(!empty($request['select'])){
        foreach($map as $code => $propName){
          if(!in_array($code, $request['select'] ?? [])){
            unset($map[$code]);
          }
        }
      }
      $rsData = new \CDBResult($rsData);
      while ($arRes = $rsData->Fetch()) {
        $obj = [];
        foreach($map as $code => $codeBase){
          $obj[$code] = $arRes[$codeBase];
        }
        if(!empty($request['key'])) {
          $result[$arRes[$request['key']]] = $obj;
        }else{
          $result[] = $obj;
        }
      }
      return ['items' => $result];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function add($data)
  {
    try {
      if(empty($data)){
        return ['error' => 'HL addEmpty => невалидные данные'];
      }
      $map = static::map();
      foreach($data as $key => $value){
        if(!empty($map[$key])){
          $data[$map[$key]] = $value;
          unset($data[$key]);
        }
      }
      $mapFlip = array_flip($map);
      foreach($data as $key => $value){
        if(empty($mapFlip[$key])){
          unset($data[$key]);
        }
      }
      $result = self::init()::add($data);
      $result = $result -> isSuccess() ?: $result -> getErrors();
      return ['update' => $result, 'data' => $data];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function update($elId,$data)
  {
    try {
      if(empty($elId) || empty($data)){
        return ['error' => 'HL updateEmpty => невалидные данные'];
      }
      $map = static::map();
      foreach($data as $key => $value){
        if(!empty($map[$key])){
          $data[$map[$key]] = $value;
          unset($data[$key]);
        }
      }
      $mapFlip = array_flip($map);
      foreach($data as $key => $value){
        if(empty($mapFlip[$key])){
          unset($data[$key]);
        }
      }
      $result = self::init()::update($elId , $data);
      $result = $result -> isSuccess() ?: $result -> getErrors();
      return ['update' => $result, 'data' => $data, 'id' => $elId];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }
}
