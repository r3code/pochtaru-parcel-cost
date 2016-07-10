<?php

    namespace r3code\Pochtaru;

    // Debug mode
    define('DEBUG',false);
    
    class PostOfficeOperationError extends \Exception { }
    
    //@Immutable
    class PostOffice {
        const ParcelCalcApiUrl = 'https://www.pochta.ru/portal-portlet/delegate/calculator/v1/api/delivery.time.cost.get'; 
        const PostingTypeVPO = 'VPO';
        const WayForwardEarth = 'EARTH';
        const PostingKindParcel = 'PARCEL';
        const ParcelKindStandard = 'STANDARD';
        const PostingCategoryOrdinary = 'ORDINARY';
        
        private $postOfficeZip;
        
        function __construct($postOfficeZip){
            if( !preg_match('/\d{6}/i', $postOfficeZip) ) {
                throw new PostOfficeOperationError(
                    'Неправильный формат индекса');
            }
            $this->postOfficeZip = $postOfficeZip;
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

        
        function CalcMailingDeliveryCost($weightGramms, $destinationPostOfficeZip, 
            $postingType, $wayForward, $postingKind, $postingCategory, $parcelKind) {
            
            if( !!!$weightGramms or !preg_match('/\d+/i', $weightGramms) 
                or $weightGramms == 0 ) {
                throw new PostOfficeOperationError('Не указан вес посылки');
            }
            if( !preg_match('/\d{6}/i', $destinationPostOfficeZip) ) {
                throw new PostOfficeOperationError(
                    sprintf('Неправильный формат индекса КОМУ, укажите 6 цифр, вместо %s', 
                    $destinationPostOfficeZip));
            }
            
            // JSON запрос
            $jsonRequest = array(
                // это поле запроса точной стоимости посылки
                'minimumCostEntity' => array(
                    'standard' => array(
                         'postingType' => $postingType,
                         'wayForward' => $wayForward,
                         'weight' => $weightGramms,
                         'zipCodeFrom' => $this->postOfficeZip,
                         'zipCodeTo' => $destinationPostOfficeZip,
                         'postingKind' => $postingKind,
                         'postingCategory' => $postingCategory,
                         'parcelKind' => $parcelKind,
                         'postalCodesFrom' => [ $this->postOfficeZip ],
                         'postalCodesTo' => [ $destinationPostOfficeZip ]
                        )
                    ),
                'productPageState' => new \ArrayObject() // обязателен в запросе, пустой объект {}
            );
            $jsonRequestEncoded = json_encode($jsonRequest);
            if (DEBUG) echo "REQUEST: $jsonRequestEncoded<br><br>";
            
            //Initiate cURL.
            $ch = curl_init(self::ParcelCalcApiUrl);
            //Tell cURL that we want to send a POST request.
            curl_setopt($ch, CURLOPT_POST, 1);
            //Attach our encoded JSON string to the POST fields.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequestEncoded);
            //Set the content type to application/json
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=UTF-8',
                'X-Requested-With: XMLHttpRequest')
            ); // возможно 'Content-Length: ' . strlen($jsonRequestEncoded) 
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //timeout in seconds 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            
            if (DEBUG) echo "<br />Запрос стоимости...";
            //Execute the request
            $jsonResponse = curl_exec($ch);
            if(curl_exec($ch) === false) {
                throw new PostOfficeOperationError('Произошла ошибка при обращении к серверу Почты России:' . curl_error($ch), curl_errno($ch));
            } else {
                $response = json_decode($jsonResponse, true);
                if (DEBUG) {
                  $debugMsg = "<br /> Запрос обработан." 
                   . "<br />СЕРВЕР ОТВЕТИЛ: \n"
                   . $jsonResponse
                   . "<br />json_decode: <br />"
                   . print_r($response, true);
                   echo $debugMsg;
                }                
               
                $responseData = $response['data'];
                $requestStatus = $response['status'];
                if( !preg_match("/OK|200/", $requestStatus) ) {
                    $errorMsg='Cервер Почты России не смог обработать запрос';
                    if (DEBUG)  $errorMsg .= ' DEBUG::' . $debugMsg;
                    throw new PostOfficeOperationError($errorMsg);
                } 
                $costInfo = $responseData['minCostResults']['standard'];
                if( !!!$costInfo ) {
                    throw new PostOfficeOperationError('Неверные параметры запроса. Cервер Почты России вернул пустой ответ');   
                }
                $calcErrors = $costInfo['errors'];
                if( count($calcErrors) > 0 ) {
                    throw new PostOfficeOperationError('Запрос выполнен Сервером Почты России с ошибками: ' . join(', ', $calcErrors));
                }
                $parcelCost = $costInfo['cost'];
                return $parcelCost;
            }
            curl_close($ch);  
                    
        }
        
        // Рассчет стоимости доставки обычной посылки по России наземным путем
        // Результат: стоимость в рублях 
        function CalcStandardParcelDeliveryCost($weightGramms, $destinationPostOfficeZip) {
            return $this->CalcMailingDeliveryCost($weightGramms, 
                $destinationPostOfficeZip, self::PostingTypeVPO, self::WayForwardEarth, 
                self::PostingKindParcel, self::PostingCategoryOrdinary, self::ParcelKindStandard);   
        }
    };
?>