<?php
// full path to source set by --include-path option of phpunit
// of by set_include_path like this one
set_include_path(dirname(__FILE__) . '/../Pochtaru/');

require_once('PostOffice.php'); 

class PostOfficeTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function CalcParcelCostOK()
    {
        $zipFrom = '119002'; // Moscow
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $weightGramms = 1400;
        $zipTo = '675000';
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipTo);
        
        $this->assertGreaterThan(0, $parcelCost);
    }
    
    /** @test  */
    public function PostOfficeCreateFailWithWrongZip() {
        $this->expectException('r3code\Pochtaru\PostOfficeOperationError');
        $this->expectExceptionMessage('Неправильный формат индекса');
        
        $zipInvalid = '6 75000'; // 
        $postOffice = new r3code\Pochtaru\PostOffice($zipInvalid);  
    }
    
    /** @test  */
    public function CalcParcelCostFailAtNonExistingZipTo() {
        $zipFrom = '119002'; 
        $weightGramms = 1400;
        $zipToNotExists = '105081'; // не существует такой индекс
        // pochta.ru changed reply phrase
        $this->expectException('r3code\Pochtaru\PostOfficeOperationError');
        $this->expectExceptionMessage('Запрос выполнен Сервером Почты России с ошибками: Индекс места назначения 105081 не существует. (1321)');
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, 
            $zipToNotExists);
            print("$parcelCost " + $parcelCost);
    }
    
    /** @test  */
    public function CalcParcelCostFailAtWrongZipFrom() {
        $zipFrom = '119002';
        $weightGramms = 1400;
        $zipToInvalidFormat = '6 75000'; // ошибка формата данных
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $this->expectException('r3code\Pochtaru\PostOfficeOperationError');
        $this->expectExceptionMessage('Неправильный формат индекса КОМУ, укажите 6 цифр, вместо 6 75000');
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipToInvalidFormat);
    }
    
    /** @test  */
    public function CalcParcelCostFailAtZeroWeight() {
        $this->expectException('r3code\Pochtaru\PostOfficeOperationError');
        $this->expectExceptionMessage('Не указан вес посылки');
        $zipFrom = '119002';
        $zipTo = '142432';
        $weightGramms = 0; // ошибка - вес 0
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipTo);
    }
    
    /** @test  */
    public function CalcParcelCostFailNoWeightValue() {
        $this->setExpectedException('r3code\Pochtaru\PostOfficeOperationError');
        $this->expectExceptionMessage('Не указан вес посылки');
        $zipFrom = '119002';
        $zipToNotExists = '675000';
        $weightGramms = ''; // ошибка - вес 0
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipToNotExists);
    }
}
?>
