<?php

/**
 * This is an optimised search for autocomplete. 
 * Its goal is to find the 15 best answers for the query as fast as possible. 
 * As opposed to the default search for autocomplete, it searches first for names that *starts* with the query, and only if it doesn't work search for the query in the middle of the names. 
 * Beside being much faster, it tend to returns more relevant results
 * It tries first to search using fast queries and only when it doesn't get enough results tries more expensive queries
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contact_getttpquick($params) {
  $result = array();
  $fastSearchLimit = 2;

  if (strlen ($name) < $fastSearchLimit) { // start with a quick and dirty
    $sql = "SELECT id, sort_name FROM civicrm_contact WHERE is_deleted=0 AND sort_name LIKE '{$params['name']}%' ORDER BY sort_name LIMIT 15";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->N == 15) { 
      while($dao->fetch()) {
        $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name);
      }
      return civicrm_api3_create_success($result, $params, 'Contact', 'getgoodquick');
    }
  }
  // search on name and first name
  $sql = "
    SELECT civicrm_contact.id, sort_name, email
    FROM civicrm_contact, civicrm_email
    WHERE (first_name LIKE '{$params['name']}%' OR sort_name LIKE '{$params['name']}%')
    AND is_deleted = 0
    AND civicrm_email.contact_id = civicrm_contact.id
    ORDER BY sort_name LIMIT 0, 25";
  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()) {
    $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name, 'email'=> $dao->email);
  }
 // if matches found less than 15, try to match from email table 
  if($dao->N < 15) { 
    $limit = 25 - $dao->N;
    // find the match from email table 
    $sql = " 
      SELECT contact_id as id, sort_name, email 
      FROM civicrm_email, civicrm_contact
      WHERE email LIKE '{$params['name']}%' 
      AND civicrm_email.contact_id = civicrm_contact.id
      AND is_deleted = 0
      ORDER BY sort_name  
      LIMIT 0, 25"; 
    $dao = CRM_Core_DAO::executeQuery($sql); 
    while($dao->fetch()) {
      $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name, "email"=> $dao->email);
    }
  }

  return civicrm_api3_create_success($result, $params, 'Contact', 'getgoodquick');
}

