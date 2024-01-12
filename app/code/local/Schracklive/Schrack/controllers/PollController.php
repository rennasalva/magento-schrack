<?php

class Schracklive_Schrack_PollController extends Mage_Core_Controller_Front_Action {

    private $_writeConnection;
    private $_readConnection;
    private $_active;
    private $_maxCounter = 5;

    public function init () {
        // Do something meanful code here, if there has to be initiated some useful stuff
        $resource = Mage::getSingleton('core/resource');
        $this->_writeConnection = $resource->getConnection('core_write');
        $this->_readConnection = $resource->getConnection('core_read');

        $this->_active = intval(Mage::getStoreConfig('schrack/shop/schrack_poll_active'));
        //Mage::log("Init Ready", null, 'poll.log');
    }


    // This function returns the next poll view datetime an writes it into localstorage
    // After localstorage (localStorage.lastViewedPoll) expired, there will be a new fetch of this function!
    public function getPollingIntervalAction() {
        $this->init();
        $lastViewedActivePoll = 'none';
        $lastViewedActiveCounter = 0;
        $result = array('last_viewed' => 'undefined',
            'last_viewed_human_readable' => 'undefined',
            'next_view' => 'undefined',
            'next_view_human_readable' => 'undefined',
            'show_now' => 'undefined',
            'show_cancel_checkbox' => 'undefined');

        if (!$this->getRequest()->isAjax()) {
            Mage::log("ajax missing (getPollingIntervalAction)", null, 'poll.err.log');
            die(); // Should not be communicated to foreigners
        }

        if ($this->_active == 1) {
            $sessionCustomerEmail = '';

            $loggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
            if ($loggedIn) {
                $sessionCustomerEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

                // First of all: check, if customer already answered questions (if yes, set it to "succeeded"):
                $query = "SELECT * FROM schrack_poll_result WHERE email LIKE '" . $sessionCustomerEmail . "'";
                $queryResult = $this->_readConnection->fetchAll($query);
                if (count($queryResult) > 0) {
                    $setSuccess  = "UPDATE schrack_poll_tracking SET status = 'succeeded'";
                    $setSuccess .= " WHERE email LIKE '" . $sessionCustomerEmail . "'";
                    $queryResult = $this->_writeConnection->query($setSuccess);

                    $result = array('last_viewed' => strtotime(date("Y-m-d H:i:s")),
                        'last_viewed_human_readable' => date("Y-m-d H:i:s"),
                        'next_view' => 'none',
                        'next_view_human_readable' => 'successful poll (already answered poll)',
                        'show_now' => 'no',
                        'show_cancel_checkbox' => 'no');

                    echo json_encode($result);
                    die();
                }

                // Find out, if this poll already shown to customer...
                $query  = "SELECT spt.id as rowid,";
                $query .= " spt.updated_at as lastviewed,";
                $query .= " spt.counter as counter,";
                $query .= " spt.cycle as cycle";
                $query .= " FROM schrack_poll_tracking spt";
                $query .= " JOIN schrack_poll sp ON spt.schrack_poll_id = sp.schrack_poll_id";
                $query .= " WHERE sp.active = 1 AND spt.email LIKE '" . $sessionCustomerEmail . "'";
                $queryResult = $this->_readConnection->fetchAll($query);
                $cycle = 0;
                if (count($queryResult) > 0) {
                    foreach ($queryResult as $index => $recordset) {
                        $lastViewedActivePoll        = $recordset['lastviewed'];
                        $lastViewedActiveCounter     = $recordset['counter'];
                        $lastViewedActiveRecordsetId = $recordset['rowid'];
                        $currentState                = $recordset['status'];
                        $cycle                       = $recordset['cycle'];
                    }

                    $currentCounter = $lastViewedActiveCounter + 1;
                    if ($currentState == 'finished' || $currentState == 'succeeded') {
                        // Do Nothing
                    } else {
                        if ($lastViewedActiveCounter < $this->_maxCounter) {
                            if ($currentCounter == $this->_maxCounter) {
                                $cycle = $cycle + 1;
                            } else {
                                $status = 'running';
                            }

                            if ($lastViewedActiveRecordsetId >= 0) {
                                // Compare timestamps, and only updates in case of minimum difference of 24 hours:
                                $lastViewedActivePollTimestamp = strtotime($lastViewedActivePoll);
                                $nowTimestamp = strtotime('now');
                                $differenceTimestamp = $nowTimestamp - $lastViewedActivePollTimestamp;
                                if ($differenceTimestamp > 86400) {
                                    // Updates the currently fetched recordset:
                                    $query  = "UPDATE schrack_poll_tracking SET updated_at = '" . date("Y-m-d H:i:s") . "',";
                                    $query .= " counter = " . $currentCounter . ",";
                                    $query .= " cycle = " . $cycle . ",";
                                    $query .= " status = '" . $status . "'";
                                    $query .= " WHERE email LIKE '" . $sessionCustomerEmail . "'";
                                    $queryResult = $this->_writeConnection->query($query);
                                }
                            } else {
                                Mage::log("Something Went Wrong Here #0001", null, 'poll.err.log');
                                $result = array('error' => 'Something Went Wrong Here #0001');
                            }
                        }
                    }
                } else {
                    $activePollId = $this->getActivePollIdAction();
                    if ($activePollId) {
                        // ... if not, then insert a new record inside tracking
                        $query  = "INSERT INTO schrack_poll_tracking SET email = '" . $sessionCustomerEmail . "', ";
                        $query .= " schrack_poll_id = " . $activePollId . ", counter = 1, status = 'running', cycle = 0,";
                        $query .= " created_at = '" . date("Y-m-d H:i:s") . "',";
                        $query .= " updated_at = '" . date("Y-m-d H:i:s") . "'";
                        $queryResult = $this->_writeConnection->query($query);
                        $lastViewedActiveCounter = 0;
                        $lastViewedActivePoll = date("Y-m-d H:i:s");
                        //Mage::log('Inserted New Poll View For Customer ' . $sessionCustomerEmail, null, 'poll.log');
                    } else {
                        Mage::log("No Active Poll ID existent", null, 'poll.err.log');
                        $result = array('error' => 'Something Went Wrong Here #0002');
                    }
                }

                if ($lastViewedActiveCounter < $this->_maxCounter) {
                    //Mage::log("Step 001", null, 'poll.log');
                    // All views, which are not the first view (gretaer than zero):
                    if ($lastViewedActivePoll != 'none' && $lastViewedActiveCounter > 0) {
                        //Mage::log("Step 002", null, 'poll.log');
                        // Dive into ruleset and fetch the next show datetime:
                        $resultFollowupRuleset = $this->followupRuleset($lastViewedActivePoll, $lastViewedActiveCounter);
                        //Mage::log("Step 003", null, 'poll.log');
                        $result = $resultFollowupRuleset;
                    } else {
                        //Mage::log("Step 004", null, 'poll.log');
                        // This is the first view of the poll:
                        $oneDay = 86400;
                        $now = strtotime(date("Y-m-d H:i:s"));
                        // In this case, just generate HTML:
                        $response = $this->getPollDataAction('internal');
                        if ($response && isset($response['html_base64'])) {
                            //Mage::log("Step 005", null, 'poll.log');
                            $result = array('last_viewed' => 'never_viewed_before',
                                'last_viewed_human_readable' => 'never_viewed_before',
                                'next_view' => strtotime(date("Y-m-d H:i:s", ($now + $oneDay))),
                                'next_view_human_readable' => date("Y-m-d H:i:s", ($now + $oneDay)),
                                'show_now' => 'yes',
                                'show_cancel_checkbox' => 'no',
                                'html_base64' => $response['html_base64']);
                        } else {
                            Mage::log("No HTML from Database as html_base64 available", null, 'poll.err.log');
                            $result = array('error' => 'Something Went Wrong Here #0003');
                        }
                    }
                } else {
                    // Check here before, if the next period has begun (check before "succeeded" or "finished"):
                    $resultFollowupRuleset = $this->followupRuleset($lastViewedActivePoll, $lastViewedActiveCounter);
                    if (isset($resultFollowupRuleset['new_cycle']) && $resultFollowupRuleset['new_cycle'] == 'yes') {
                        $result = $resultFollowupRuleset;
                    } else {
                        $twoWeeks = 1123200; // 14 x 86400 - one day = 1123200
                        $now = strtotime(date("Y-m-d H:i:s"));

                        $result = array('last_viewed' => strtotime($lastViewedActivePoll),
                            'last_viewed_human_readable' => $lastViewedActivePoll,
                            'next_view' => strtotime(date("Y-m-d H:i:s", ($now + $twoWeeks))),
                            'next_view_human_readable' => 'counter limit reached (2 weeks wait state)',
                            'show_now' => 'no',
                            'show_cancel_checkbox' => 'no');
                    }
                }
            } else {
                $result = array('result' => 'customer is not logged in (poll)');
            }
        } else {
            Mage::log("No Active Poll", null, 'poll.err.log');
        }
        //Mage::log($result, null, 'poll.log');
        echo json_encode($result);
        die();
    }


    public function getPollDataAction($modus = 'external') {
        $response = array('error' =>'No Poll Data Config Found (->getPollDataAction())');
        $query = "SELECT * FROM schrack_poll WHERE active = 1";
        $queryResult = $this->_readConnection->fetchAll($query);
        if (count($queryResult) > 0) {
            //Mage::log("Step 006", null, 'poll.log');
            foreach ($queryResult as $index => $recordset) {
                $size   = $recordset['size'];
                $pollId = $recordset['schrack_poll_id'];
                $html   = $recordset['html'];
            }

            if ($html) {
                //Mage::log("Step 007", null, 'poll.log');
                $response = array();
                $response['html_base64'] = $html;
            } else {
                if ($pollId > 0 && $size > 0) {
                    //Mage::log("Step 008", null, 'poll.log');
                    $query = "SELECT * FROM schrack_poll_config WHERE schrack_poll_id = " . $pollId . " ORDER by id";
                    $queryResult = $this->_readConnection->fetchAll($query);
                    if (count($queryResult) > 0) {
                        //Mage::log("Step 009", null, 'poll.log');
                        foreach ($queryResult as $index => $recordset) {
                            $pollData[$index][$recordset['category']] = array(
                                                                   'sts_key' => $recordset['sts_key'],
                                                                       'pos' => $recordset['position'],
                                                               'answer_type' => $recordset['answer_type']);
                        }
                        if ($pollData) {
                            $response = array();
                            $response['html_base64'] = base64_encode($this->parsePollData($pollData, $size, $pollId));
                        }
                    }
                }
            }
        } else {
            Mage::log("No Poll Data Config Found (->getPollDataAction())", null, 'poll.err.log');
        }

        if ($modus == 'external') {
            echo json_encode($response);
            die();
        }
        if ($modus == 'internal') {
            return $response;
        }
    }


    private function parsePollData($pollData, $size, $pollId) {
        //Mage::log("Step 010", null, 'poll.log');
        if (is_array($pollData) && !empty($pollData)) {
            //Mage::log("Step 011", null, 'poll.log');
            $html = '';
            foreach ($pollData as $i => $data) {
                if (key($data) == 'question') {
                    if ($i > 0) {
                        $html .= '<br>';
                    }
                    $pos = $data['question']['pos'];
                    $stsKey = $data['question']['sts_key'];
                    $html .= '<div class="poll_question">' . $this->__($stsKey) . '</div>';
                    if ($data['question']['answer_type'] == 'textarea') {
                        $id = 'poll_answer_' . $pos . '_textarea';
                        $html .= '<div><textarea class="poll_answer_textarea poll_answer" id="' . $id . '"></textarea></div>';
                    }
                    if ($data['question']['answer_type'] == 'text') {
                        $id = 'poll_answer_' . $pos . '_text';
                        $html .= '<div><input type="text" class="poll_answer_text poll_answer" id="' . $id . '"></div>';
                    }
                    if ($data['question']['answer_type'] == 'radiogroup1+textarea') {
                        //$html .= $this->answerGroups('radiogroup1', $pos);
                        $html .= $this->answerGroups('radiogroup1', $pos);
                        $id = 'poll_answer_' . $pos . '_text';
                        $html .= '<div><textarea class="poll_answer_textarea poll_answer" id="' . $id . '"></textarea></div>';
                    }
                    if ($data['question']['answer_type'] == 'radiogroup1+text') {
                        //$html .= $this->answerGroups('radiogroup1', $pos);
                        $html .= $this->answerGroups('radiogroup1', $pos);
                        $id = 'poll_answer_' . $pos . '_text';
                        $html .= '<div><input type="text" class="poll_answer_text poll_answer" id="' . $id . '"></div>';
                    }
                    if ($data['question']['answer_type'] == 'radiogroup1') {
                        $html .= $this->answerGroups('radiogroup1', $pos);
                    }
                    if ($data['question']['answer_type'] == 'radiogroup2') {
                        $html .= $this->answerGroups('radiogroup2', $pos);
                    }
                    if ($data['question']['answer_type'] == 'radiogroup3') {
                        $html .= $this->answerGroups('radiogroup3', $pos);
                    }
                    if ($data['question']['answer_type'] == 'radiogroup4') {
                        $html .= $this->answerGroups('radiogroup4', $pos);
                    }
                    // Question-Types:
                    // 'text' : Answer is only a text string
                    // 'select+text' : Answer is consisting of a text string AND a select list ('Yes'/'No'/'No Comment')
                    // 'select' : Answer is only a select list
                    // 'radiogroup1' / 'radiogroup2' : Answer is a radio group (single answer possible)
                    // 'checkboxes' : Answer is devided in multiple checkboxes (multiple answers possible)
                } else {
                    $pos = $data['answer']['pos'];
                    if ($data['answer']['answer_type'] == 'select+text') {
                        // TODO
                    }
                    if ($data['answer']['answer_type'] == 'select') {
                        // TODO
                    }
                    if ($data['answer']['answer_type'] == 'radiogroup1+textarea') {
                        //$html .= $this->answerGroups('radiogroup1', $pos);
                        $html .= $this->answerGroups('radiogroup1', $pos);
                        $id = 'poll_answer_' . $pos . '_text';
                        $html .= '<div><textarea class="poll_answer_textarea poll_answer" id="' . $id . '"></textarea></div>';
                    }
                    if ($data['answer']['answer_type'] == 'radiogroup1+text') {
                        //$html .= $this->answerGroups('radiogroup1', $pos);
                        $html .= $this->answerGroups('radiogroup1', $pos);
                        $id = 'poll_answer_' . $pos . '_text';
                        $html .= '<div><input type="text" class="poll_answer_text poll_answer" id="' . $id . '"></div>';
                    }
                    if ($data['answer']['answer_type'] == 'radiogroup1') {
                        $html .= $this->answerGroups('radiogroup1', $pos);
                    }
                    if ($data['answer']['answer_type'] == 'radiogroup2') {
                        $html .= $this->answerGroups('radiogroup2', $pos);
                    }
                    if ($data['answer']['answer_type'] == 'radiogroup3') {
                        $html .= $this->answerGroups('radiogroup3', $pos);
                    }
                    if ($data['answer']['answer_type'] == 'radiogroup4') {
                        $html .= $this->answerGroups('radiogroup4', $pos);
                    }
                    if ($data['answer']['answer_type'] == 'check') {
                        $stsKey = $data['answer']['sts_key'];
                        $checkbox  = '<div>';
                        $checkbox .= '<input type="checkbox" class="poll_check" id="' . $stsKey . '"';
                        $checkbox .= ' name="' . $stsKey . '" value="' . $stsKey . '">';
                        $checkbox .= '<label class="poll_checkbox_label" for="' . $stsKey . '">&nbsp;' . $this->__($stsKey) . '</label>';
                        $checkbox .= '</div>';
                        $html .= $checkbox;
                    }
                    if ($data['answer']['answer_type'] == 'check+textarea') {
                        $stsKey = $data['answer']['sts_key'];
                        $checkbox  = '<div>';
                        $checkbox .= '<input type="checkbox" class="poll_check" id="' . $stsKey . '"';
                        $checkbox .= ' name="' . $stsKey . '" value="' . $stsKey . '">';
                        $checkbox .= '<label class="poll_checkbox_label" for="' . $stsKey . '">&nbsp;' . $this->__($stsKey) . '</label>';
                        $checkbox .= '</div>';
                        $html .= $checkbox;
                        $id = 'poll_answer_' . $pos . '_text';
                        $html .= '<div><textarea class="poll_answer_textarea poll_answer" id="' . $id . '"></textarea></div>';
                    }
                }
            }

            // Cache all HTML into DB :
            $query = "UPDATE schrack_poll SET html = '" . base64_encode($html) . "' WHERE schrack_poll_id = " . $pollId;
            //Mage::log($query, null, 'poll.log');
            $queryResult = $this->_writeConnection->query($query);
        }

        //Mage::log($html, null, 'poll.html.log');

        return $html;
    }


    public function setPollDataAction() {
        // TODO
    }


    private function followupRuleset($lastViewedActivePollDatetime, $lastViewedActiveCounter) {
        // Define rules are set for show popup again:
        // 1. First rule: every day only one time (max = 5 times altogether, then show "close forever")
        $result = array();
        $oneDay = 86400;
        $twoWeeks = 1209600; // 14 x 86400
        $now = strtotime(date("Y-m-d H:i:s"));

        if ($lastViewedActiveCounter == $this->_maxCounter) {
            // Check new cycle:
            $lastViewedTimestamp = strtotime($lastViewedActivePollDatetime);
            if (($lastViewedTimestamp + $twoWeeks) < $now) {
                $this->resetCounter();
                $result = array('last_viewed' => strtotime($lastViewedActivePollDatetime),
                    'last_viewed_human_readable' => $lastViewedActivePollDatetime,
                    'next_view' => strtotime(date("Y-m-d H:i:s", ($now + $oneDay))),
                    'next_view_human_readable' => date("Y-m-d H:i:s", ($now + $oneDay)),
                    'show_now' => 'yes',
                    'show_cancel_checkbox' => 'no',
                    'new_cycle' => 'yes');
            } else {
                $result = array('last_viewed' => strtotime($lastViewedActivePollDatetime),
                    'last_viewed_human_readable' => $lastViewedActivePollDatetime,
                    'next_view' => 'none',
                    'next_view_human_readable' => 'none',
                    'show_now' => 'yes',
                    'show_cancel_checkbox' => 'yes');
            }
        }

        if ($lastViewedActiveCounter < $this->_maxCounter) {
            //Mage::log("Step #X001", null, 'poll.log');
            $lastViewedTimestamp = strtotime($lastViewedActivePollDatetime);
            //Mage::log($lastViewedActivePollDatetime, null, 'poll.log');
            if (($lastViewedTimestamp + $oneDay) < $now) {
                //Mage::log("Step #X002", null, 'poll.log');
                $result = array('last_viewed' => strtotime($lastViewedActivePollDatetime),
                    'last_viewed_human_readable' => $lastViewedActivePollDatetime,
                    'next_view' => strtotime(date("Y-m-d H:i:s", ($now + $oneDay))),
                    'next_view_human_readable' => date("Y-m-d H:i:s", ($now + $oneDay)),
                    'show_now' => 'yes',
                    'show_cancel_checkbox' => 'no');
            }
        }

        //Mage::log('followupRuleset', null, 'poll.log');
        //Mage::log($result, null, 'poll.log');

        // Return Array:
        // 'next_view' => '<CALCULATED_DATETIME>'
        // 'show_cancel_checkbox' => '<YES/NO>'
        return $result;
    }


    private function getActivePollIdAction() {
        $query = "SELECT schrack_poll_id FROM schrack_poll WHERE active = 1";
        $queryResult = $this->_readConnection->fetchOne($query);
        if (count($queryResult) > 0) {
            return $queryResult;
        } else {
            return null;
        }
    }


    private function answerGroups($answerGroup, $number) {
        // radiogroup1 = 'YES' / 'NO' / 'NO Comment'
        if ($answerGroup == 'radiogroup1') {
            $name = 'poll_answer_' . $number . '_radio';
            $id = $this->generateUuid();
            $html  = '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_YES">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_YES') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NO">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_NO') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NOT_SPECIFIED">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_NOT_SPECIFIED') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<input style="display: none;" id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NO_ANSWER" checked>';
        }

        // radiogroup2 = 'NEVER' / 'SELDOM' / 'OFTEN' / 'NO Comment'
        if ($answerGroup == 'radiogroup2') {
            $name = 'poll_answer_' . $number . '_radio';
            $id = $this->generateUuid();
            $html  = '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NEVER">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_NEVER') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_RARELY">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_RARELY') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_OFTEN">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_OFTEN') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NOT_SPECIFIED">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_NOT_SPECIFIED') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<input style="display: none;" id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NO_ANSWER" checked>';
        }

        // radiogroup3 = 'DAILY' / 'WEEKLY' / 'NEVER' / 'ONCE A MONTH' / 'NO Comment'
        if ($answerGroup == 'radiogroup3') {
            $name = 'poll_answer_' . $number . '_radio';
            $id = $this->generateUuid();
            $html  = '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_DAILY">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_DAILY') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_WEEKLY">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_WEEKLY') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NEVER">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_NEVER') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_ONCE_A_MONTH">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_ONCE_A_MONTH') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NOT_SPECIFIED">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_NOT_SPECIFIED') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<input style="display: none;" id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NO_ANSWER" checked>';
        }

        if ($answerGroup == 'radiogroup4') {
            $name = 'poll_answer_' . $number . '_radio';
            $id = $this->generateUuid();
            $html  = '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_DESKTOP">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_DESKTOP') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_MOBIL">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_MOBIL') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<div><input id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_BOTH">';
            $html .= '<label class="poll_label" for="' . $id . '">&nbsp;' . $this->__('POLL_BOTH') . '</label></div>';
            $id = $this->generateUuid();
            $html .= '<input style="display: none;" id="' . $id . '" type="radio" class="poll_radio poll_radio_answer" name="' . $name . '" value="POLL_NO_ANSWER" checked>';
        }

        return $html;
    }


    public function setPollingDataAction() {
        if (!$this->getRequest()->isAjax()) {
            Mage::log("ajax missing (setPollingDataAction)", null, 'poll.err.log');
            die(); // Should not be communicated to foreigners
        }

        $params = $this->getRequest()->getParams();

        if (is_array($params) && !empty($params)) {
            $this->init();
            //Mage::log($params, null, 'poll.success.log');
            // Get Poll-ID first:
            $pollId = '';
            $query = "SELECT * FROM schrack_poll WHERE active = 1";
            $queryResult = $this->_readConnection->fetchAll($query);

            if (count($queryResult) > 0) {
                foreach ($queryResult as $index => $recordset) {
                    $pollId = $recordset['schrack_poll_id'];
                }
            }

            if ($pollId) {
                //Mage::log($pollId, null, 'poll.success.log');
                $sessionCustomerEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
                $status = 'succeeded';

                foreach($params as $key => $value) {
                    $type        = '';
                    $position    = 0;
                    $translation = '';
                    //Mage::log($key . ' -> ' . $value, null, 'poll.success.log');
                    if (stristr($key, '_text')) {
                        $type = 'textfield';
                        $position = str_replace(array('poll_answer_', '_textarea'), '', $key);
                    }
                    if (stristr($key, '_textarea')) {
                        $type = 'textarea';
                        $position = str_replace(array('poll_answer_', '_text'), '', $key);
                    }
                    if (stristr($key, '_radio')) {
                        $type = 'radiobutton';
                        $position = str_replace(array('poll_answer_', '_radio'), '', $key);
                        $translation = $this->__($value);
                    }
                    if (stristr($key, '_CHECK')) {
                        $type = 'checkbox';
                        $replaceChunk = 'POLL_' . $pollId . '_ANSWER_';
                        $chunkKey = str_replace($replaceChunk, '', $key);
                        $position = substr($chunkKey, 0, 1);
                        $translation = $this->__($value);
                    }

                    $query = "INSERT INTO schrack_poll_result SET";
                    $query .= " created_at = '" . date("Y-m-d H:i:s") . "',";
                    $query .= " email = :customer_email,";
                    $query .= " schrack_poll_id = :poll_id,";
                    $query .= " position = :pos,";
                    $query .= " answer = :answer_value,";
                    $query .= " answer_translated = :translation,";
                    $query .= " answer_type = :type";
                    //Mage::log($query, null, 'poll.success.log');
                    // All NON-STS Keys, will only inserted into "answer_translated" column:
                    if (!stristr($value, 'POLL_')) {
                        $translation = $value;
                        $value = '';
                    }
                    // Special Case -> no radiobutton selected:
                    if($translation == 'POLL_NO_ANSWER') {
                        $translation = '';
                    }
                    $binds = array(
                        'poll_id' => $pollId,
                        'customer_email' => $sessionCustomerEmail,
                        'pos' => $position,
                        'answer_value' => $value,
                        'translation' => $translation,
                        'type' => $type
                    );
                    //Mage::log($binds, null, 'poll.success.log');
                    $queryResult = $this->_writeConnection->query($query, $binds);
                }

                $query  = "UPDATE schrack_poll_tracking SET status = '" . $status . "',";
                $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
                $query .= " WHERE email LIKE '" . $sessionCustomerEmail . "'";
                $queryResult = $this->_writeConnection->query($query);
            }
        }
        //Mage::log($params, null, "poll.log");
    }


    private function generateUuid() {
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return $uuid;
    }


    private function resetCounter() {
        $this->init();
        $sessionCustomerEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        // Find out, if this poll already shown to customer...
        $query  = "SELECT spt.id as rowid,";
        $query .= " spt.updated_at as lastviewed,";
        $query .= " spt.counter as counter,";
        $query .= " spt.cycle as cycle";
        $query .= " FROM schrack_poll_tracking spt";
        $query .= " JOIN schrack_poll sp ON spt.schrack_poll_id = sp.schrack_poll_id";
        $query .= " WHERE sp.active = 1 AND spt.email LIKE '" . $sessionCustomerEmail . "'";
        $queryResult = $this->_readConnection->fetchAll($query);

        if (count($queryResult) > 0) {
            foreach ($queryResult as $index => $recordset) {
                $lastViewedActivePoll        = $recordset['lastviewed'];
                $previousCycle               = $recordset['cycle'];
            }
        }
        $lastViewedActivePollTimestamp = strtotime($lastViewedActivePoll);
        $nowTimestamp = strtotime('now');
        $differenceTimestamp = $nowTimestamp - $lastViewedActivePollTimestamp;
        if ($differenceTimestamp > 86400) {
            // Updates the currently fetched recordset:
            $query  = "UPDATE schrack_poll_tracking SET updated_at = '" . date("Y-m-d H:i:s") . "',";
            $query .= " counter = 1,";
            $query .= " cycle = " . ($previousCycle + 1) . ",";
            $query .= " status = 'running'";
            $query .= " WHERE email LIKE '" . $sessionCustomerEmail . "'";
            $queryResult = $this->_writeConnection->query($query);
        }
    }
}
