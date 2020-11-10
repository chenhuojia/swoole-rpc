<?php
namespace Application\Controller;
use Application\Model as Model;

class Account{

	private $accountModel = null;

  public function __construct(){

		$this->accountModel = new Model\Account();

	}	

  public function login($params){
    $loginResult = $this->accountModel->login();
    return [$params,$loginResult];
  }

    public function login2($params,$id){
        $loginResult = $this->accountModel->login();
        return [$params,$id,$loginResult];
    }
}
