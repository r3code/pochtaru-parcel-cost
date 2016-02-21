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
        $zipFrom = '105082'; // Moscow
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $weightGramms = 1400;
        $zipTo = '142432';
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipTo);
        
        $this->assertGreaterThan(0, $parcelCost);
    }
    
    /** @test  */
    public function PostOfficeCreateFailWithWrongZip() {
        $this->setExpectedException('r3code\Pochtaru\PostOfficeOperationError', 
            'Неправильный формат индекса');
        
        $zipInvalid = '1 05081'; // 
        $postOffice = new r3code\Pochtaru\PostOffice($zipInvalid);  
    }
    
    /** @test  */
    public function CalcParcelCostFailAtNonExistingZipTo() {
        $this->setExpectedException('r3code\Pochtaru\PostOfficeOperationError', 
            'Запрос выполнен Сервером Почты России с ошибками: Индекс места получения 105081 не существует. (1321), null (1361)');
        
        $zipFrom = '105082'; 
        $weightGramms = 1400;
        $zipToNotExists = '105081'; // не существует такой индекс
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, 
            $zipToNotExists);
    }
    
    /** @test  */
    public function CalcParcelCostFailAtWrongZipFrom() {
        $this->setExpectedException('r3code\Pochtaru\PostOfficeOperationError', 
            'Неправильный формат индекса КОМУ, укажите 6 цифр, вместо 1 05081');
        
        $zipFrom = '105082';
        $weightGramms = 1400;
        $zipToNotExists = '1 05081'; // ошибка формата данных
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipToNotExists);
    }
    
    /** @test  */
    public function CalcParcelCostFailAtZeroWeight() {
        $this->setExpectedException('r3code\Pochtaru\PostOfficeOperationError', 
            'Не указан вес посылки');
        $zipFrom = '105082';
        $zipToNotExists = '142432';
        $weightGramms = 0; // ошибка - вес 0
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipToNotExists);
    }
    
    /** @test  */
    public function CalcParcelCostFailNoWeightValue() {
        $this->setExpectedException('r3code\Pochtaru\PostOfficeOperationError', 
            'Не указан вес посылки');
        $zipFrom = '105082';
        $zipToNotExists = '142432';
        $weightGramms = ''; // ошибка - вес 0
        $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipToNotExists);
    }
}
?>