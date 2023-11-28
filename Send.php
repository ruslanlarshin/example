<?php


namespace B24;

use Bitrix\Highloadblock as HL;
use \Vitamax\{IBlock};
use \Catalog\{Price};

class Send
{
  public const vitamaxCompanyId = 109;
  public const api = [
    'createOrder' => '/crestApi/b2b/createOrder.php',
    'ordersList' => '/crestApi/b2b/getOrdersByAdc.php',
    'orderDetail' => '/crestApi/b2b/orderDetail.php',
    'sendDifference' => '/crestApi/b2b/sendDifference.php',
    'orderCompleted' => '/crestApi/b2b/orderCompleted.php',
    'adcAdd' => '/crestApi/b2b/adcAdd.php',
    'taskAdd' => '/crestApi/b2b/taskAdd.php',
    'updateAdcProperty' => '/crestApi/b2b/tools/updateAdcProperty.php',
    'updateAdcType' => '/crestApi/b2b/tools/updateAdcType.php',
    'updateAdcDelivery' => '/crestApi/b2b/tools/updateDelivery.php',
    'errorSubName' => '/crestApi/orders/errorSubName/index.php',
    'orderStatus' => '/crestApi/orders/changeStatus/index.php',
    'updateDeal' => '/crestApi/orders/updateDeal/index.php',
    'statusDeal' => '/crestApi/b2b/orderStatus.php',
    'updateAdc' => '/crestApi/b2b/orders/updateAdc/index.php',
    'updateBasket' => '/crestApi/b2b/orders/updateBasket/index.php',
    'getByB2BId' => '/crestApi/b2b/orders/getByB2BId/index.php',
    'getFinalFF' => '/crestApi/b2b/orders/getFinal/index.php',
    'sdtDoc' => '/crestApi/b2b/orders/sdtDoc/index.php',
    'deleteDoc' => '/crestApi/b2b/docs/delete.php',
  ];

  public static function curlBody($url, $data){
    $options = [
      'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data ?? [])
      ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return ['curl' => $result];
  }

  //Генерируем промежуточную накладную тор12 между витамакс рус и поставщикмо , идетв  поле все заказа TORG_12_PROVIDER
  public static function sdtDocProvider($orderId){
    $orderDetail = \Vitamax\OrderDetail::getByOrderId($orderId)['order'];
    if(empty($orderDetail['onec']['docNumber'])){
      return ['error' => "Данные для накладной еще не получены из 1с"];
    }
    $dealId = $orderDetail['b24']['id'];
    if(empty($dealId)){
      return ['error' => 'Введите номер заказа'];
    }
    $params = [
      'dealId' => $dealId
    ];
    $provider = \Catalog\Order::getProviderByOrderId($orderId)['provider'];
    if($provider['id'] == self::vitamaxCompanyId){
      return ['error' => "Поставщик и посредник одна компания- промежуточная ведомость не требуется!"];
    }
    $vitamax = \Vitamax\Company::getCompanyById(self::vitamaxCompanyId)['company'];
    if($provider['id'] == 107){//TODO у Планеты здоровья укажем оба адреса((
      $provider["addressString"] = "Юр. адрес : {$provider["addressString"]}, почтовый адрес : {$provider["address"]['name']}";
    }
    if(!empty($provider)){
      $params['requisite'] = [
        //Грузоотправитель
        "MyCompanyRequisiteRqCompanyName" => $vitamax["name"],
        "MyCompanyRequisiteRegisteredAddressText" => $vitamax["addressString"],
        "MyCompanyRequisiteRqInn" => $vitamax["requisite"]['inn'],
        "MyCompanyBankDetailRqAccNum" => $vitamax["requisite"]['paymentAccount'],
        "MyCompanyBankDetailRqBankName" => $vitamax["requisite"]['bankName'],
        "MyCompanyBankDetailRqCorAccNum" => $vitamax["requisite"]['korr'],
        "MyCompanyBankDetailRqBik" => $vitamax["requisite"]['bik'],
        "MyCompanyUfStamp" => \Service\Settings::siteUrl . $vitamax["print"] ?? '',
        "MyCompanyUfDirectorSign" => \Service\Settings::siteUrl . $vitamax["signature"] ?? '',
        "MyCompanyUfDirectorSign2" => \Service\Settings::siteUrl . $vitamax["signature"] ?? '',
        //"Position" => $provider['name'],
        "UfCrm1590063924499" =>  $vitamax["director"],
        "UfCrm1679494831394" =>  $vitamax['onec']['docNumber'],
        "UfCrm1614159061978" =>  date('d.m.Y'),
        "MyCompanyRequisiteRqAccountant" =>  $vitamax["director"],

        //Грузополучатель
        "CompanyTitle" => $provider["name"],
        "CompanyRequisiteDeliveryAddressText" => $provider["addressString"],
        "RequisiteRqInn" => $provider["requisite"]['inn'],
        "BankDetailRqAccNum" => $provider["requisite"]['paymentAccount'],
        "BankDetailRqBankName" => $provider["requisite"]['bankName'],
        "BankDetailRqCorAccNum" => $provider["requisite"]['korr'],
        "BankDetailRqBik" => $provider["requisite"]['bik'],
      ];
    }
    $params['full'] = true;
    $params['requisite']['provider'] = 'provider';
    return json_decode(self::curlBody(self::hostName . self::api['sdtDoc'], $params)['curl'], true)['result'];
  }


  public static function sdtDoc($dealId, $full = false, $orderId = null){
    if(empty($dealId)){
      return ['error' => 'Введите номер заказа'];
    }
    $params = [
      'dealId' => $dealId
    ];
    $orderDetail = \Vitamax\OrderDetail::getByOrderId($orderId)['order'];
    $provider = \Catalog\Order::getProviderByOrderId($orderId)['provider'];
    if($provider['id'] == 107){//TODO у Планеты здоровья укажем оба адреса((
      $provider["addressString"] = "Юр. адрес : {$provider["addressString"]}, почтовый адрес : {$provider["address"]['name']}";
    }
    if(!empty($provider)){
      $params['requisite'] = [
        "MyCompanyRequisiteRqCompanyName" => $provider["name"],
        "MyCompanyRequisiteRegisteredAddressText" => $provider["addressString"],
        "MyCompanyRequisiteRqInn" => $provider["requisite"]['inn'],
        "MyCompanyBankDetailRqAccNum" => $provider["requisite"]['paymentAccount'],
        "MyCompanyBankDetailRqBankName" => $provider["requisite"]['bankName'],
        "MyCompanyBankDetailRqCorAccNum" => $provider["requisite"]['korr'],
        "MyCompanyBankDetailRqBik" => $provider["requisite"]['bik'],
        "MyCompanyUfStamp" => \Service\Settings::siteUrl . $provider["print"] ?? '',
        "MyCompanyUfDirectorSign" => \Service\Settings::siteUrl . $provider["signature"] ?? '',
        "MyCompanyUfDirectorSign2" => \Service\Settings::siteUrl . $provider["signature"] ?? '',
        //"Position" => $provider['name'],
        "UfCrm1590063924499" =>  $provider["director"],
        "UfCrm1679494831394" =>  $orderDetail['onec']['docNumber'],
        "UfCrm1614159061978" =>  date('d.m.Y'),
        "MyCompanyRequisiteRqAccountant" =>  $provider["director"],
      ];
    }
    if($full && !empty($orderId)){
      //$orderDetail['onec']['docNumber'] = $orderDetail['onec']['docNumber'] ?? $orderId;
      if(empty($orderDetail['onec']['docNumber'])){
        return ['error' => "Данные для накладной еще не получены из 1с"];
      }
      $params['full'] = true;
    }
    return json_decode(self::curlBody(self::hostName . self::api['sdtDoc'], $params)['curl'], true)['result'];
  }

  public static function deleteDocs($data){
    if(empty($data['id'])){
      return ['error' => 'Введите номер заказа'];
    }
    return json_decode(self::curlBody(self::hostName . self::api['deleteDoc'], $data)['curl'], true)['result'];
  }

  public static function getByB2bId($data){
    if(empty($data['orderId'])){
      return ['error' => 'Введите номер заказа'];
    }
    return json_decode(self::curlBody(self::hostName . self::api['getByB2BId'], $data)['curl'], true)['result'];
  }

  public static function getFinalFF(){
    return json_decode(self::curlBody(self::hostName . self::api['getFinalFF'], [])['curl'], true)['result'];
  }

  public static function updateBasket($data){
    if(empty($data['orderId']) || empty($data['basket'])){
      return ['error' => 'Введите номер заказа и корзину'];
    }
    return self::curlBody(self::hostName . self::api['updateBasket'], $data)['curl'];
  }

  public static function updateOrderAdc($data){
    if(empty($data['orderId']) || empty($data['storeId'])){
      return ['error' => 'Введите номер заказа и ошибку'];
    }
    return self::curlBody(self::hostName . self::api['updateAdc'], $data)['curl'];
  }

  public static function errorSubName($data){
    if(empty($data['orderId'])){
      return ['error' => 'Введите номер заказа и ошибку'];
    }
    return self::curlBody(self::hostName . self::api['errorSubName'], $data)['curl'];
  }

  public static function statusDeal($data){
    if(empty($data['orderId'])){
      return ['error' => 'Введите номер заказа'];
    }
    return self::curlBody(self::hostName . self::api['statusDeal'], $data)['curl'];
  }

  public static function updateDeal($data){
    if(empty($data['orderId'])){
      return ['error' => 'Введите номер заказа'];
    }
    return self::curlBody(self::hostName . self::api['updateDeal'], $data)['curl'];
  }

  public static function changeStatus($data){
    if(empty($data['orderId']) || empty($data['status'])){
      return ['error' => 'Введите номер заказа и ошибку'];
    }
    return self::curlBody(self::hostName . self::api['orderStatus'], $data)['curl'];
  }

  public static function updateAdcProperty($data){
    $data['elementId'] = \Vitamax\Adc::getAdcById($data['elementId'])['adc']['bitrix24'];
    return self::curlBody(self::hostName . self::api['updateAdcProperty'], $data)['curl'];
  }

 public static function updateAdcType($data){
    $data['elementId'] = \Vitamax\Adc::getAdcById($data['adcId'])['adc']['bitrix24'];
    return self::curlBody(self::hostName . self::api['updateAdcType'], $data)['curl'];
  }

  public static function updateAdcDelivery($data){
    $data['elementId'] = \Vitamax\Adc::getAdcById($data['elementId'])['adc']['bitrix24'];
    return self::curlBody(self::hostName . self::api['updateAdcDelivery'], $data)['curl'];
  }

  public static function updateAdcActive($data){
    $data['elementId'] = \Vitamax\Adc::getAdcById($data['elementId'])['adc']['bitrix24'];
    $data['propertyName'] = 'AKTIVNOST';
    $data['value'] = ($data['value'] == 'Y') ? 101 : 103;
    return self::curlBody(self::hostName . self::api['updateAdcProperty'], $data)['curl'];
  }

  public static function createOrder($data){
    return json_decode(self::curlBody(self::hostName . self::api['createOrder'], $data)['curl'], true);
  }

  public static function orderCompleted($data){
    if(empty($data['orderId'])){
      return ['error' => 'Необходимо передать идентификатор сделки'];
    }
    return self::curlBody(self::hostName . self::api['orderCompleted'], $data)['curl'];
  }

  public static function orderDetail($orderId){
    return json_decode(self::curlBody(self::hostName . self::api['orderDetail'], ['orderId' => $orderId])['curl'], true)['order'];
  }

  public static function ordersList($adcStoreId){
    $data = [
      'adcStoreId' => $adcStoreId,
    ];
    return json_decode(self::curlBody(self::hostName . self::api['ordersList'], $data)['curl'], true)[0];
  }

  public static function sendDifference($data){
    return json_decode(self::curlBody(self::hostName . self::api['sendDifference'], $data)['curl'], true);
  }

  public static function adcAdd($data){
    return json_decode(self::curlBody(self::hostName . self::api['adcAdd'], $data)['curl'], true);
  }

  public static function taskAdd($data){
    return json_decode(self::curlBody(self::hostName . self::api['taskAdd'], $data)['curl'], true);
  }
}
