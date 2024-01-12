<?php
/**
 * Created by IntelliJ IDEA.
 * User: d.laslov
 * Date: 06.08.2015
 * Time: 14:57
 */
class Schracklive_SchrackCustomer_Model_Acceptoffertracking extends Schracklive_SchrackCustomer_Model_AbstractTracking {

    public function __construct () {
        parent::__construct();
        $this->_init('schrackcustomer/acceptoffertracking');
    }

}