<?php

    namespace r3code\Pochtaru;

    // Debug mode
    define('DEBUG', false);
    
    function debug_msg($msg) {
        if (DEBUG) {
            echo $msg;     
        }        
    }
    
    class PostOfficeOperationError extends \Exception { }
    
    //@Immutable
    class PostOffice {
        const PARCEL_CALC_API_URL = 'https://www.pochta.ru/portal-portlet/delegate/calculator/v1/api/delivery.time.cost.get'; 
        const POSTING_TYPE_VPO = 'VPO';
        const WAY_FORWARD_EARTH = 'EARTH';
        const POSTING_KIND_PARCEL = 'PARCEL';
        const PARCEL_KIND_STANDARD = 'STANDARD';
        const POSTING_CATEGORY_ORDINARY = 'ORDINARY';
        
        private $postOfficeZip;
        
        function __construct($postOfficeZip){
            if( !preg_match('/\d{6}/i', $postOfficeZip) ) {
                throw new PostOfficeOperationError(
                    'Неправильный формат индекса');
            }
            $this->postOfficeZip = $postOfficeZip;
        }
        // raises PostOfficeOperationError if curl error found
        private function DoRequestData($jsonRequestEncoded) {
            $requestTimeoutSec = 10;
            //Initiate cURL.
            $curlObj = curl_init(self::PARCEL_CALC_API_URL);
            //Tell cURL that we want to send a POST request.
            curl_setopt($curlObj, CURLOPT_POST, 1);
            //Attach our encoded JSON string to the POST fields.
            curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonRequestEncoded);
            //Set the content type to application/json
            curl_setopt($curlObj, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=UTF-8',
                'X-Requested-With: XMLHttpRequest')
            ); // возможно 'Content-Length: ' . strlen($jsonRequestEncoded) 
            curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, $requestTimeoutSec); //timeout in seconds 
            curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, TRUE);   
            //Execute the request
            try {
                $jsonResponse = curl_exec($curlObj);
                if ($jsonResponse === false) 
                    throw new PostOfficeOperationError('Произошла ошибка при обращении к серверу Почты России:' 
                    . curl_error($curlObj), curl_errno($curlObj));
            } finally {
                curl_close($curlObj);    
            }
            return $jsonResponse;
        }
        // raises PostOfficeOperationError if prams invalid
        private function CheckParcelParams($weightGramms, $destPostOfficeZip) {
            $weightInvalid = !!!$weightGramms or !preg_match('/\d+/i', $weightGramms) 
                or $weightGramms == 0;   
            if( $weightInvalid ) {
                throw new PostOfficeOperationError('Не указан вес посылки');
            }
            $zipInvalid = !preg_match('/\d{6}/i', $destPostOfficeZip);
            if( $zipInvalid ) {
                throw new PostOfficeOperationError(
                    sprintf('Неправильный формат индекса КОМУ, укажите 6 цифр, вместо %s', 
                    $destPostOfficeZip));
            }    
        }
        // returns json string
        private function PrepareRequest($weightGramms, $destPostOfficeZip, 
            $postingType, $wayForward, $postingKind, $postingCategory, 
            $parcelKind) {
            // JSON запрос
            $jsonRequest = array(
                // form 10.2016 pochta.ru requires field "costCalculationEntity" to be present in query
                'costCalculationEntity' => array(
                    'postingType' => $postingType,
                    'zipCodeFrom' => $this->postOfficeZip,
                    'zipCodeTo' => $destPostOfficeZip,
                    'postalCodesFrom' => [ $this->postOfficeZip ],
                    'postalCodesTo' => [ $destPostOfficeZip ],
                    'weightRange' => [ 100, $weightGramms ],
                    'wayForward' => $wayForward,
                    'postingKind' => $postingKind,
                    'postingCategory' => $postingCategory,
                    'parcelKind' => $parcelKind
                    ),
                // get precise parcel cost
                'minimumCostEntity' => array(
                    'standard' => array(
                         'postingType' => $postingType,
                         'wayForward' => $wayForward,
                         'weight' => $weightGramms,
                         'zipCodeFrom' => $this->postOfficeZip,
                         'zipCodeTo' => $destPostOfficeZip,
                         'postingKind' => $postingKind,
                         'postingCategory' => $postingCategory,
                         'parcelKind' => $parcelKind,
                         'postalCodesFrom' => [ $this->postOfficeZip ],
                         'postalCodesTo' => [ $destPostOfficeZip ]
                        )
                    ),
                // обязателен в запросе, пустой объект {}
                'productPageState' => new \ArrayObject() 
            );
            $jsonRequestEncoded = json_encode($jsonRequest);
            return $jsonRequestEncoded;
        }
        // raises exception if error
        private function CheckResponseErrors($requestStatus, $costInfo) {
            if( !preg_match("/OK|200/", $requestStatus) ) {
                debug_msg('Request ERROR, status ' . $requestStatus);
                $errorMsg='Cервер Почты России не смог обработать запрос. Ошибка: ' 
                    . $requestStatus;
                throw new PostOfficeOperationError($errorMsg);
            }    
            if( !!!$costInfo ) {
                throw new PostOfficeOperationError('Неверные параметры запроса. Cервер Почты России вернул пустой ответ');   
            }
            $calcErrors = $costInfo['errors'];
            if( count($calcErrors) > 0 ) {
                throw new PostOfficeOperationError('Запрос выполнен Сервером Почты России с ошибками: ' 
                    . join(', ', $calcErrors));
            }
        }
        
        function GetPostingTypes() {
            // TODO: получить весь список с http://pochta.ru/parcel
            return array('VPO');
        } 
        
        function GetForwardingWays() {
            // TODO: получить весь список с http://pochta.ru/parcel
            return array('EARTH');
        } 
        
        function GetPostingKinds() {
            // TODO: получить весь список с http://pochta.ru/parcel
            return array('PARCEL');
        } 
        
        function GetPostingCategories() {
            // TODO: получить весь список с http://pochta.ru/parcel
            return array('ORDINARY');
        }
        function GetParcelKinds() {
            // TODO: получить весь список с http://pochta.ru/parcel
            return array('STANDARD');
        }

        
        function CalcMailingDeliveryCost($weightGramms, $destPostOfficeZip, 
            $postingType, $wayForward, $postingKind, $postingCategory, 
            $parcelKind) {
            // raises an exception if fails
            $this->CheckParcelParams($weightGramms, $destPostOfficeZip);
            // JSON запрос
            $jsonRequestEncoded = $this->PrepareRequest($weightGramms, 
                $destPostOfficeZip, $postingType, $wayForward, $postingKind, 
                $postingCategory, $parcelKind);
            
            debug_msg("REQUEST: $jsonRequestEncoded<br><br>");
            debug_msg("<br />Request cost...");
            // raises Exception if failed
            $jsonResponse = $this->DoRequestData($jsonRequestEncoded);
           
            $response = json_decode($jsonResponse, true);
            debug_msg("<br /> Request done." 
               . "<br />Server Response: \n"
               . $jsonResponse
               . "<br />json_decode: <br />"
               . print_r($response, true));
           
            $responseData = $response['data'];
            $requestStatus = $response['status'];
            $costInfo = $responseData['minCostResults']['standard'];
            $this->CheckResponseErrors($requestStatus, $costInfo);
            $parcelCost = $costInfo['cost'];
            return $parcelCost;
            
        }
        
        // Calculate cost delivery by land transportation in Russia
        // ru_RU::Рассчет стоимости доставки обычной посылки по России наземным путем
        // @result: cost in russian rubles
        function CalcStandardParcelDeliveryCost($weightGramms, 
            $destPostOfficeZip) {
            return $this->CalcMailingDeliveryCost($weightGramms, 
                $destPostOfficeZip, self::POSTING_TYPE_VPO, 
                self::WAY_FORWARD_EARTH, 
                self::POSTING_KIND_PARCEL, self::POSTING_CATEGORY_ORDINARY, 
                self::PARCEL_KIND_STANDARD);   
        }
    };
?>