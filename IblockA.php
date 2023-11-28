<?
//Author Larshin Ruslan 89105580875
namespace Main;

\CModule::IncludeModule('iblock');

abstract class IblockA
{
  public const special = [
    'NAME' => 'Наименование',
    'ID' => 'ID',
    'MODIFIED_BY' => 'Изменен',
    'ACTIVE' => 'Активность',
    'CODE' => 'Код',
    'SORT' => 'Сортировка',
    'IBLOCK_SECTION_ID' => 'Раздел',
    'DATE_CREATE' => 'Дат создания',
    'DETAIL_TEXT' => 'Детальное описание',
    'PREVIEW_TEXT' => 'Анонс'
  ];

  abstract static protected function map();
  abstract static protected function iblockCode();

  protected static function getMap(){
    $map = [];
    foreach(static::map() as $key => $item){
      $map = array_merge($map ?: [], $item);
    }
    return $map;
  }

  public static function select($map)
  {
    return array_values($map);
  }

  public static function getFieldForAddUpdate($request)
  {
    $result = [
      "IBLOCK_ID" => self::getIdByCode()['id'],
    ];
    foreach (static::getMap() as $code => $property) {
      if (isset($request[$code])) {
        if (in_array($property, array_keys(self::special))) {
          if(!empty($request[$code])) {
            $result[$property] = $request[$code];
          }
        }
        else {
          $propCode = str_replace('PROPERTY_', '', $property);
          $result['PROPERTY_VALUES'][$propCode] = $request[$code];
        }
      }
    }
    return $result;
  }

  public static function getListFormat($request = [])
  {
    try {
      $format = static::map();
      $items = [];
      $list = self::getList($request)['items'];
      if(empty($format)){
        return ['error' => 'Пустой массив форматированного вывода'];
      }
      foreach($list as $keyList => $item) {
        $result = [];
        foreach ($format as $keyGroup => $group) {
          if (empty($request['format']) || in_array($keyGroup, $request['format'] ?? [])) {
            $result[$keyGroup] = [];
            foreach ($group as $keyProperty => $codeBB) {
              if (empty($request['select']) || in_array($keyProperty, $request['select'] ?? [])) {
                $result[$keyGroup][$keyProperty] = $item[$keyProperty];
              }
            }
            if (empty($result[$keyGroup])) {
              unset($result[$keyGroup]);
            }
          }
        }
        $items[$keyList] = $result;
      }
      return ['items' => $items];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }


  public static function deleteEmpty($idElement)
  {
    try {
      if (empty($idElement)) {
        return ['error' => 'Невалидные данные'];
      }
      $element = self::getList(['filter' => ['ID' => intval($idElement)],'select' => ['id'], 'limit' => 1])['items'][0];
      if(!empty($element['id'])){
        \CIBlockElement::Delete($idElement);
      }
      return true;

    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }



  public static function update($idElement, $request)
  {
    try {
      if (empty($idElement)) {
        return ['error' => 'Невалидные данные'];
      }
      $el = new \CIBlockElement;
      $fields = self::getFieldForAddUpdate($request);
      $property = $fields['PROPERTY_VALUES'];
      unset($fields['PROPERTY_VALUES']);
      if (!empty($property)) {
        \CIBlockElement::SetPropertyValuesEx($idElement, static::iblockCode(), $property);
      }
      $newId = $el->Update($idElement, $fields);
      if (!empty($newId)) {
        //\Main\Cache::clearTagCache(static::iblockCode());
        return ['success' => true, 'id' => $newId, 'data' => $fields, 'property' => $property, 'method' => 'update'];
      }
      else {
        return ['error' => $el->LAST_ERROR];
      }
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function add($request, $doubleControl = false)
  {
    try {
      if (empty($request['name'])) {
        return ['error' => 'Невалидные данные'];
      }
      if($doubleControl){
        $item = self::getList([
          'filter' => [
            'NAME' => $request['name'] ?? $request['NAME'],
          ],
          'nav' => ['limit' => 1],
        ])['items'];
        if(!empty($item)){
          return ['error' => ['text' => 'Дубль записи!','item' => $item] ];
        }
      }
      $el = new \CIBlockElement;
      $fields = self::getFieldForAddUpdate($request);
      $newId = $el->Add($fields);
      if(!empty($newId)){
        \Main\Cache::clearTagCache(static::iblockCode());
        return ['success' => true, 'id' => $newId, 'data' => $fields, 'method' => 'add'];
      }else{
        return ['error' => $el->LAST_ERROR];
      }
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  private static function specialFilter($property, $getMap = false){
    $code = str_replace(['>=', '<=', '=', '!', '<', '>'],'',$property);
    if($code !== $property){
      $getMap = $getMap ?: static::getMap();
      $special = str_replace($code, '', $property);
      return $special . ($getMap[$code] ?: $code);
    }
    return $getMap[$code] ?: $code;
  }

  public static function getList($request = [], $iblockCode = false, $getMap = false)
  {
    try {
      $getMap = $getMap ?: static::getMap();
      $request['sort'] = $request['sort'] ?? $request['order'];
      if ($request['sort']) {
        foreach ($request['sort'] as $sortKey => $sort) {
          if (!empty($sort) && in_array($sort, ['asc', 'desc', 'rand'])) {
            $sort = strtoupper($sort);
            $request['sort'][$sortKey] = $sort;
          }
          if (!empty($sort) && !in_array($sort, ['ASC', 'DESC', 'RAND'])) {
            unset($request['sort'][$sortKey]);
          }
          if (!empty($sortKey) && !empty($getMap[$sortKey])) {
            $request['sort'][$getMap[$sortKey]] = $sort;
            unset($request['sort'][$sortKey]);
          }
        }
      }

      if(!empty($request['group']) && !empty($getMap[$request['group']])){
        if(!empty($request['select'])){
          return ['error' => 'Нельзя указывать группировку и селект'];
        }
        if(!empty($request['nav'])){
          return ['error' => 'Нельзя указывать группировку и навигацию'];
        }
        if(!empty($request['sort'])){
          return ['error' => 'Нельзя указывать группировку и sort'];
        }
        $getMap = [$request['group'] => $getMap[$request['group']]];
      }

      if(!empty($request['filter'])) {
        foreach ($request['filter'] as $code => $filter) {
          if($code === 'or' || $code === 'OR'){
            $logic['LOGIC'] = 'OR';
            //$logic[] = ['LOGIC' => 'OR'];
            foreach($request['filter'][$code] as $codeOr => $filterOr){
              if(is_int($codeOr)) {
                // $logic['LOGIC'] = 'OR';
                $buf = [];
                foreach ($filterOr as $codeOrItem => $filterOrItem) {
                  $buf[self::specialFilter($codeOrItem, $getMap)] = $filterOrItem;
                }
                $logic[] = $buf;
              }elseif(!empty($getMap[$codeOr])){
                $logic[] = [self::specialFilter($codeOr, $getMap) => $filterOr];
              }
            }
            unset($request['filter']['or']);
            unset($request['filter']['OR']);
            $request['filter'][] = $logic;
          }else{
            $request['filter'][self::specialFilter($code, $getMap)] = $filter;
            if(!empty($getMap[$code])) {
               unset( $request['filter'][$code]);
            }
          }
        }
      }

      $request['filter']['IBLOCK_CODE'] = $iblockCode ?: static::iblockCode();

      if(!empty($request['select'])){
        foreach($getMap as $code => $propName){
          if(!in_array($code, $request['select'] ?? [])){
            unset($getMap[$code]);
          }
        }
      }
      $nav = [];
      if(!empty($request['nav']['limit']) || !empty($request['limit'])){
        $nav['nTopCount'] = $request['nav']['limit'] ?: $request['limit'];
      }
      if(!empty($request['nav']['offset'])  || !empty($request['offset'])){
        $nav['nOffset'] = $request['nav']['offset'] ?: $request['offset'];
      }

      if($request['cacheTime']){
        $cacheTime = $request['cacheTime'];
        unset($request['cacheTime']);
        $result = \Main\Cache::tagged('\Main\IBlockA::getList', $iblockCode?: static::iblockCode(), $getMap, $request, $request, $cacheTime)['items'];
        return ['items' => $result];
      }
      $res = \CIBlockElement::GetList(
        $request['sort'] ?? [],
        $request['filter'] ?? [],
        !empty($request['group']) ? [$getMap[$request['group']] ?? $request['group']] : false,
        $nav ?: false,
        empty($request['group']) ? self::select($getMap) : false
      );
      $result = [];
      if(!empty($request['key'])) {
        $key = !(empty($getMap[$request['key']])) ? ($getMap[$request['key']]) : $request['key'];
        $key = (!empty(self::special[$key]) || (strpos($key, 'VALUE') !== false)) ? $key : $key . '_VALUE';//TODO enumList
      }
      while ($ob = $res->GetNextElement()) {
        $arFields = $ob->GetFields();

        if(!empty($request['key']) && !empty($arFields[$key])) {
          if(is_array($arFields[$key])){
            return ['error' => 'Ключом не может стать множественное значение'];
          }
          if(empty($arFields[$key])){
            $arFields[$key] = 'none';
          }elseif(!empty($result[$arFields[$key]])){
            return ['error' => 'По данному запросу ключа выявлены дублирующие значения'];
          }
          $result[$arFields[$key]] = self::formatFields($arFields, $iblockCode, $getMap);
        }else{
          if(empty($request['group'])) {
            $result[] = self::formatFields($arFields, $iblockCode, $getMap);
          }else{
            $result[$arFields['CNT']] = $arFields[($getMap[$request['group']] ?: $request['group']) . '_VALUE'];
          }
        }
      }
      return ['items' => $result];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function formatFields($arFields, $iblockCode = false, $getMap = false)
  {
    $properties = self::getProperties($iblockCode);
    $result = [];
    $getMap = $getMap ?: static::map();
    foreach ($getMap as $code => $property) {
      if($property == 'NAME'){
        $arFields[$property] = html_entity_decode($arFields[$property]);
      }
      if (in_array($property, array_keys(self::special))) {
        $result[$code] = $arFields[$property];
      }
      else {
        $propCode = str_replace('PROPERTY_', '', $property);
        $property = $property . '_VALUE';
        if ($properties[$propCode]['type'] == 'F') {
          if ($properties[$propCode]['multiply']) {
            $result[$code] = \Main\Files::getFilesProperty($arFields[$property])['files'];
          }
          else {
            $result[$code] = \Main\Files::getFileProperty($arFields[$property])['file'];
          }
        }
        elseif (($properties[$propCode]['type'] == 'S') && ($properties[$propCode]['typeName'] == 'HTML')) {
          $result[$code] = $arFields['~' . $property]['TEXT'];
        }
        else {
          $result[$code] = $arFields[$property];
        }
      }

    }
    return $result;
  }

  public static function getProperties($iblockCode = false)
  {
    try {
      $result = [];
      $properties = \CIBlockProperty::GetList(
        [],
        [
          'IBLOCK_CODE' => $iblockCode ?: static::iblockCode(),
        ]
      );
      while ($propFields = $properties->GetNext()) {
        if (empty($result[$propFields['CODE']])) {
          $result[$propFields['CODE']] = [
            'id' => $propFields['ID'],
            'name' => $propFields['NAME'],
            'code' => $propFields['CODE'],
            'type' => $propFields['PROPERTY_TYPE'],
            'typeName' => $propFields['USER_TYPE'],
            'multiply' => ($propFields['MULTIPLE'] === "Y") ? true : false,
          ];
        }
      }
      return $result;
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }


  public static function getPropertiesName($iblockCode = false){
    $properties = self::getProperties($iblockCode = false);
    $result = [];
    foreach(static::map() as $map){
      foreach($map as $code => $property){
        if(in_array($property, array_keys(self::special))){
          $result[$code] = [
            'name' => self::special[$property],
            'code' => $property,
          ];
        }else{
          $prop = str_replace('PROPERTY_', '',$property);
          if(!empty($properties[$prop])){
            $result[$code] = $properties[$prop];
          }
        }
      }
    }
    return $result;
  }


  public static function getIdByCode($iblockCode = false)
  {
    try {
      $res = \CIBlock::GetList(
        [],
        [
          'CODE' => $iblockCode ?: static::iblockCode(),
        ],
        true
      );
      if ($arRes = $res->Fetch()) {
        return ['id' => intval($arRes['ID'])];
      }
      return 0;
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function getSectionId($sectionCode)
  {
    try {
      $dbList = \CIBlockSection::GetList([], ['CODE' => $sectionCode, 'IBLOCK_CODE' => static::iblockCode()], false, ['ID', 'NAME', 'CODE']);
      while ($arResult = $dbList->GetNext()) {
        return ['id' => intval($arResult['ID'])];
      }
      return ['error' => 'Не найден'];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function getSectionList()
  {
    try {
      $result = [];
      $dbList = \CIBlockSection::GetList([], ['IBLOCK_CODE' => static::iblockCode()], false, ['ID', 'NAME', 'CODE']);
      while ($arResult = $dbList->GetNext()) {
        $result[$arResult['CODE']] = [
          'id' => intval($arResult['ID']),
          'code' => $arResult['CODE'],
          'name' => $arResult['NAME'],
        ];

      }
      if (!empty($result)) {
        return ['list' => $result];
      }
      return ['error' => 'Не найден'];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function getPropertyEnumList( $propertyCode)
  {
    try {
      $fields = [];
      foreach(static::map() as $item){
        $fields = array_merge($fields ?: [], $item);
      }
      $propertyCode = str_replace('PROPERTY_', '',$fields[$propertyCode]) ?: $propertyCode;
      $property_enums = \CIBlockPropertyEnum::GetList(
        [],
        [
          "IBLOCK_CODE" => static::iblockCode(),
          "CODE" => $propertyCode
        ]
      );
      $result = [];
      while ($enum_fields = $property_enums->GetNext()) {
        $result[$enum_fields["XML_ID"]] = [
          'id' => intval($enum_fields["ID"]),
          'code' => $enum_fields["XML_ID"],
          'name' => $enum_fields["VALUE"],
          'codeFilter' => 'PROPERTY_' . $propertyCode,
        ];
      }
      return ['items' => $result];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function getSections()
  {
    try {
      $rsSections = \CIBlockSection::GetList(
        [
          'SORT' => 'ASC',
          'ID' => 'ASC'
        ],
        [
          'IBLOCK_CODE' => static::iblockCode(),
          'ACTIVE' => 'Y',
        ]
      );
      $result = [];
      while ($arSection = $rsSections->Fetch())
      {
        $result[intval($arSection['ID'])] = [
          'id' => intval($arSection['ID']),
          'code' => $arSection['CODE'],
          'name' => $arSection['NAME'],
        ];
      }
      return ['menu' => $result];
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }
  public static function getPager($pageCount, $pageNumber = 1){
    $result = [];
    if($pageCount <= 6){
      for($i=1;$i<=$pageCount;$i++)
      {
        $result['pagers'][] = ['code' => $i, 'urlCode' => $i];
      }
    }else{
      if($pageNumber <= 4)
      {
        for($i=1;$i<=$pageNumber+1;$i++){
          $result['pagers'][] = ['code' => $i, 'urlCode' => $i];
        }
        $result['pagers'][] = ['code' => '...', 'urlCode' => ceil(($pageCount-$pageNumber+1)/2)];
        $result['pagers'][] = ['code' => $pageCount, 'urlCode' => $pageCount];
      }else{
        $result['pagers'][] = ['code' => 1, 'urlCode' => 1];
        $result['pagers'][] = ['code' => '...', 'urlCode' => ceil(($pageNumber-2)/2)];
        $result['pagers'][] = ['code' => $pageNumber - 1, 'urlCode' => $pageNumber - 1];
        $result['pagers'][] = ['code' => $pageNumber, 'urlCode' => $pageNumber];

        if($pageNumber != $pageCount && ($pageCount-$pageNumber) > 3){
          $result['pagers'][] = ['code' => $pageNumber + 1, 'urlCode' => $pageNumber + 1];
        }

        if(($pageCount-$pageNumber) <= 3)
        {
          for($i=$pageNumber+1;$i<=$pageCount;$i++){
            $result['pagers'][] = ['code' => $i, 'urlCode' => $i];
          }
        }else{
          $result['pagers'][] = ['code' => '...', 'urlCode' => ceil(($pageCount-$pageNumber-1)/2+$pageNumber)];
          $result['pagers'][] = ['code' => $pageCount, 'urlCode' => $pageCount];
        }
      }

      foreach($result['pagers'] as $key => $page){
        if($page['code'] == $pageNumber){
          $result['pagers'][$key]['active'] = true;
        }
      }
    }
    return $result;
  }

}
