<?php

namespace DaData;

use \Bitrix\Crm;

\CModule::IncludeModule('crm');

//require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php"); обязатлен guzzlehttp

class DaData
{
 

  public const iblockCode = 'dadata';

  public static function getMap(){
    return [
        'id' => 'ID',
        'name' => 'NAME',
        'result' => 'PROPERTY_RESULT',
        'json' => 'PROPERTY_JSON',
        'adc' => 'PROPERTY_ADC',

        'geo' => [
          'lon' => 'PROPERTY_GEO_LON',
          'lat' => 'PROPERTY_GEO_LAT',
        ],
        'address' => [
          'country' => 'PROPERTY_COUNTRY',
          'region' => 'PROPERTY_REGION',
          'area' => 'PROPERTY_AREA',
          'city' => 'PROPERTY_CITY',
          'street' => 'PROPERTY_STREET',
          'building' => 'PROPERTY_BUILDING',
          'flat' => 'PROPERTY_FLAT',
          'index' => 'PROPERTY_INDEX',
        ],
      'fias' => [
        'region' => 'PROPERTY_REGION_FIAS',
        'area' => 'PROPERTY_AREA_FIAS',
        'city' => 'PROPERTY_CITY_FIAS',
        'street' => 'PROPERTY_STREET_FIAS',
        'building' => 'PROPERTY_BUILDING_FIAS',
      ],
      'kladr' => [
        'region' => 'PROPERTY_REGION_KLADR',
        'area' => 'PROPERTY_AREA_KLADR',
        'city' => 'PROPERTY_CITY_KLADR',
        'street' => 'PROPERTY_STREET_KLADR',
        'building' => 'PROPERTY_BUILDING_KLADR',
      ],
    ];
  }

  public static function cleanPhone($phone){
    try {
      if (empty($phone)) {
        return ['error' => 'Невалидный телефон'];
      }
      //$phone = (array)(self::init()->cleanPhone($phone));
      $buf = \Bitrix\Main\Analytics\Catalog::normalizePhoneNumber($phone);//TODO экономия денег
      $phone =
        [
          'atomic' => $buf,
          'phone' => $buf,
        ];
      //$phone['atomic'] = preg_replace('/[^0-9]/', '', trim($phone['phone']));
      return ['phone' => $phone];
    } catch (GuzzleHttp\Exception\ClientException $ex) {
      return ['error' => $ex];
    }
  }

  private static function init(){
    return  new \Dadata\Client(new \GuzzleHttp\Client(), [
      'token' => self::token,
      'secret' => self::secret,
    ]);
  }

  public static function getAddress($address, $adc = false){
    try{
      if(empty($address)){
        return ['error' => 'Невалидный адрес'];
      }
      $dadata = self::getByName($address)['address'];
      if(empty($dadata)) {
        $addressDD = (array)(self::init()->cleanAddress($address));
        $dadata = [
          'name' => $address,
          'result' => $addressDD['result'],
          'json' => json_encode($addressDD),
          'adc' => $adc,

          'geo' => [
            'lon' => $addressDD['geo_lon'],
            'lat' => $addressDD['geo_lat'],
          ],
          'address' => [
            'country' => $addressDD['country'],
            'region' => $addressDD['region_with_type'],
            'area' => $addressDD['area_with_type'],
            'city' => $addressDD['city_with_type'] ?: $addressDD['city_district'] ?: $addressDD['settlement'],
            'street' => $addressDD['street_with_type'],
            'building' => $addressDD['house_type_full'] . ' ' . $addressDD['house'],
            'flat' => $addressDD['flat_type_full'] . ' ' . $addressDD['flat'],
            'index' => $addressDD['postal_code'],
          ],
          'fias' => [
            'region' => $addressDD['region_fias_id'],
            'area' => $addressDD['area_fias_id'],
            'city' => $addressDD['city_fias_id'] ?: $addressDD['city_district_fias_id'] ?: $addressDD['settlement_fias_id'],
            'street' => $addressDD['street_fias_id'],
            'building' => $addressDD['house_fias_id'],
          ],
          'kladr' => [
            'region' => $addressDD['region_kladr_id'],
            'area' => $addressDD['area_kladr_id'],
            'city' => $addressDD['city_kladr_id'] ?: $addressDD['city_district_kladr_id'] ?: $addressDD['settlement_kladr_id'],
            'street' => $addressDD['street_kladr_id'],
            'building' => $addressDD['house_kladr_id'],
          ],
        ];
        $dadata['id'] = self::add($dadata)['id'];
      }
      return ['address' => $dadata];
    }catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function getByName($name){
    try {
      if (empty($name)) {
        return ['error' => 'Невалидный адрес'];
      }
      $res = \CIBlockElement::GetList(
        [],
        [
          'IBLOCK_CODE' => self::iblockCode,
          'NAME' => $name,
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'PROPERTY_RESULT', 'PROPERTY_JSON', 'PROPERTY_ADC', 'PROPERTY_GEO_LON',
          'PROPERTY_GEO_LAT', 'PROPERTY_COUNTRY', 'PROPERTY_REGION', 'PROPERTY_AREA', 'PROPERTY_CITY',
          'PROPERTY_STREET', 'PROPERTY_STREET', 'PROPERTY_BUILDING', 'PROPERTY_FLAT', 'PROPERTY_INDEX',
          'PROPERTY_REGION_FIAS', 'PROPERTY_AREA_FIAS', 'PROPERTY_CITY_FIAS', 'PROPERTY_STREET_FIAS', 'PROPERTY_BUILDING_FIAS',
          'PROPERTY_REGION_KLADR', 'PROPERTY_AREA_KLADR', 'PROPERTY_CITY_KLADR', 'PROPERTY_STREET_KLADR', 'PROPERTY_BUILDING_KLADR',
          ]
      );
      if ($ob = $res->GetNextElement()) {
        $arFields = $ob->GetFields();
        $dadata = [
          'id' => $arFields['ID'],
          'name' => $arFields['NAME'],
          'result' => $arFields['PROPERTY_RESULT_VALUE'],
          'json' => $arFields['PROPERTY_JSON_VALUE']['TEXT'],
          'adc' => $arFields['PROPERTY_ADC_VALUE'],

          'geo' => [
            'lon' => $arFields['PROPERTY_GEO_LON_VALUE'],
            'lat' => $arFields['PROPERTY_GEO_LAT_VALUE'],
          ],
          'address' => [
            'country' => $arFields['PROPERTY_COUNTRY_VALUE'],
            'region' => $arFields['PROPERTY_REGION_VALUE'],
            'area' => $arFields['PROPERTY_AREA_VALUE'],
            'city' => $arFields['PROPERTY_CITY_VALUE'] ,
            'street' => $arFields['PROPERTY_STREET_VALUE'],
            'building' => $arFields['PROPERTY_BUILDING_VALUE'],
            'flat' => $arFields['PROPERTY_FLAT_VALUE'],
            'index' => $arFields['PROPERTY_INDEX_VALUE'],
          ],
          'fias' => [
            'region' => $arFields['PROPERTY_REGION_FIAS_VALUE'],
            'area' => $arFields['PROPERTY_AREA_FIAS_VALUE'],
            'city' => $arFields['PROPERTY_CITY_FIAS_VALUE'],
            'street' => $arFields['PROPERTY_STREET_FIAS_VALUE'],
            'building' => $arFields['PROPERTY_BUILDING_FIAS_VALUE_VALUE'],
          ],
          'kladr' => [
            'region' => $arFields['PROPERTY_REGION_KLADR_VALUE'],
            'area' => $arFields['PROPERTY_AREA_KLADR_VALUE'],
            'city' => $arFields['PROPERTY_CITY_KLADR_VALUE'],
            'street' => $arFields['PROPERTY_STREET_KLADR_VALUE'],
            'building' => $arFields['PROPERTY_BUILDING_KLADR_VALUE'],
          ],
        ];
        return ['address' => $dadata];
      }
    }catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }

  public static function add($request)
  {
    try {
      $el = new \CIBlockElement;
      $arLoadProductArray = [
        "MODIFIED_BY" => \Vitamax\User::getId(),
        "IBLOCK_SECTION_ID" => $request['sectionId'],
        "IBLOCK_ID" => \Vitamax\IBlock::getIdByCode(self::iblockCode)['id'],
        "PROPERTY_VALUES" => [
          'RESULT' => $request['result'],
          'ADC' => $request['adc'],
          'JSON' => $request['json'],

          'GEO_LAT' => $request['geo']['lat'],
          'GEO_LON' => $request['geo']['lon'],

          'COUNTRY' => $request['address']['country'],
          'REGION' => $request['address']['region'],
          'AREA' => $request['address']['area'],
          'CITY' => $request['address']['city'],
          'STREET' => $request['address']['street'],
          'BUILDING' => $request['address']['building'],
          'FLAT' => $request['address']['flat'],
          'INDEX' => $request['address']['index'],

          'REGION_FIAS' => $request['fias']['region'],
          'AREA_FIAS' => $request['fias']['area'],
          'CITY_FIAS' => $request['fias']['city'],
          'STREET_FIAS' => $request['fias']['street'],
          'BUILDING_FIAS' => $request['fias']['building'],

          'REGION_KLADR' => $request['kladr']['region'],
          'AREA_KLADR' => $request['kladr']['area'],
          'CITY_KLADR' => $request['kladr']['city'],
          'STREET_KLADR' => $request['kladr']['street'],
          'BUILDING_KLADR' => $request['kladr']['building'],

        ],
        "NAME" => $request['name'],
        "CODE" => $request['code'],
        "ACTIVE" => "Y",
      ];
      if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
        return ['success' => true, 'id' => $PRODUCT_ID];
      }
      else {
        return ['error' => $el->LAST_ERROR];
      }
    } catch (\Bitrix\Main\SystemException $ex) {
      return ['error' => $ex];
    }
  }
}