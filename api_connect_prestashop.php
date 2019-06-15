<?php

class ApiConnect
{
  const TOKEN = 'IF7ENX7FA4CMHXBA1SSV3EDHMUFH8K55';
  const API_URL = 'http://prestashop.quentinbesnard.fr/api/orders?filter[current_state]=[1|2|9|10|11|12|13|14]&limit=0,1&sort=[id_DESC]';
  
  private $context;
  private $lastOrderId;

  public function __construct() {
      exec('gpio mode 0 out');
      exec('gpio mode 3 down');
      
      $auth = base64_encode(self::TOKEN.':');
      $this->context = stream_context_create([
          'http' => [
              'header' => "Authorization: Basic $auth",
          ],
      ]);
      $this->setLedOff();
      $this->lastOrderId = $this->getLastOrderId();
  }

  public function listen() {
    do {     
        // If alert is off, check if there is a new one
        if (true === $this->isNewOrderAvailable() && false === $this->getLedState())  {
            $this->setLedOn();
        }

        // If the alert is on, check if the button is pushed
        if (true === $this->getLedState() && true === $this->getSwitchState()) {
            $this->setLedOff();
        }

      sleep(1);
    } while(true);
  }

  public function setLedOn() {
    exec('gpio write 0 1');
  }
	
  public function setLedOff() {
    exec('gpio write 0 0');
  }

  public function getSwitchState() {
    return (bool) exec('gpio read 3');
  }
	
  public function getLedState() {
     return (bool) exec('gpio read 0');
  }

  public function isNewOrderAvailable() {
    $id = $this->getLastOrderId();

    if ($id !== $this->lastOrderId) {
      $this->setLastOrderId($id);
      
      return true;
    }
    
    return false;
  }

  function getLastOrderId() {
    $lastOrder = simplexml_load_string(file_get_contents(self::API_URL, false, $this->context));
    $lastOrder = (array) $lastOrder->orders->order;
    
    return (int) $lastOrder['@attributes']['id'];
  }

  function setLastOrderId($id) {
    $this->lastOrderId = $id;
  }
}

$apiConnect = new ApiConnect();
$apiConnect->listen();
