<?

namespace B2B;

\CModule::IncludeModule('iblock');


class CommissionerA extends \Main\IblockA
{
  use \Trait\Singleton;
  public $store;

  private function __construct() {
    $userId = \Vitamax\User::getId();
    if(!empty($userId)) {
      $storeId = \B2B\CommissionerA::getList(
        [
          'filter' => ['user' => $userId],
          'limit' => 1,
          'select' => ['storeSdt', 'provider'],
          'cacheTime' => \Main\Cache::cacheTime,
        ]
      )['items'][0]['storeSdt'];
      if(empty($storeId)){
        return ['error' => 'Не указан склад поставщик!'];
      }
      $store = \Store\StoresA::getList(
        [
          'filter' => ['id' => $storeId],
          'select' => ['code', 'id', 'name'],
          'limit' => 1,
        ]
      )['items'][0];
    }else{
      return ['error' => 'Доступно только для авторизованных!'];
    }
    $this -> store = $store;
  }

  public static function iblockCode(){
    return 'Adc';
  }

  public static function map()
  {
    return [
      'main' =>
        [
          'id' => 'ID',
          'active' => 'ACTIVE',
          'store' => 'PROPERTY_RECIPIENT',
          'name' => 'NAME',
          'code' => 'CODE',
          'type' => 'PROPERTY_TYPE',
          'storeSdt' => 'PROPERTY_STORE_SDT',
          'provider' => 'PROPERTY_PROVIDER',
        ],

      'contacts' => [
        'user' => 'PROPERTY_USER',
        'managers' => 'PROPERTY_MANAGERS',
        'urAddress' => 'PROPERTY_UR_ADDRESS',
        'postAddress' => 'PROPERTY_POST_ADDRESS',
        'phone' => 'PROPERTY_PHONE',
        'delivery' => 'PROPERTY_DELIVERY',
      ],

      '1c' => [
        'id1C' => 'PROPERTY_ID_ONE_C',
        'name1c' => 'PROPERTY_NAME_1C',
        'contractDeliveryId' => 'PROPERTY_CONTRACT',
      ],

      'limits' => [
        'limit' => 'PROPERTY_LIMIT',
        'balance' => 'PROPERTY_COMISSION',
        'debt' => 'PROPERTY_DEBT',
        'limitCommon' => 'PROPERTY_LIMIT_GOODS',
        'productWayPrice' => 'PROPERTY_PRODUCT_WAY_PRICE',
      ],

      'requisite' => [
        'bankName' => 'PROPERTY_BANK_NAME',
        'bankCity' => 'PROPERTY_BANK_CITY',
        'bankPaymentAccount' => 'PROPERTY_BANK_PAYMENT_ACCOUNT',
        'bankInn' => 'PROPERTY_BANK_INN',
        'bankBik' => 'PROPERTY_BANK_BIK',
        'bankKorr' => 'PROPERTY_BANK_KORR',
      ],

      'service' => [
        'commentBlock' => 'PROPERTY_COMMENT',
        'reserve' => 'PROPERTY_RESERVE',
        'inventoryCache' => 'PROPERTY_INVENTORY_CACHE',
        'inventoryDopGoods' => 'PROPERTY_INVENTORY_DOP_GOODS',
        'bitrix24' => 'PROPERTY_BITRIX24_ID',
        'updateTime' => 'PROPERTY_UPDATE_TIME',
        'confirmData' => 'PROPERTY_CONFIRM_DATA',
        'confirmFlag' => 'PROPERTY_CONFIRM_FLAG',
      ],
    ];
  }



}