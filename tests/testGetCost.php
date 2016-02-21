<?php
  
  require_once('../Pochtaru/PostOffice.php'); 
  $zipFrom = '105082'; // Moscow
  $postOffice = new r3code\Pochtaru\PostOffice($zipFrom);  
  //print_r($postOffice);
  echo 'Расчет стоимости посылки...';
  try {
        $weightGramms = 150;
        $zipTo = '142432';
        $parcelCost = $postOffice->CalcStandardParcelDeliveryCost($weightGramms, $zipTo);
        echo sprintf('Стоимость посылки %d руб.',$parcelCost);
    } catch(Exception $e) {
        echo "<br><b>Ошибка</b>, " . $e->getMessage();  
  }
  
?>