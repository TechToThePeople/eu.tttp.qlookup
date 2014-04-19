<?php

function _civicrm_api3_contact_getttpquick_spec(&$params) {
  $params['name']['api.aliases'] = array('sort_name');
  $params['name']['api.required'] = 1;
  $params['with_email_only']['api.default'] = false;
  $params['fastSearchLimit']['api.default'] = 1;
  $params['option_limit']['api.default'] = 20;
  $params['return']['api.default'] = "sort_name,email";
}

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
  $name = mysql_real_escape_string ($params['name']);
  $N= $params["option_limit"];
  if (!is_numeric($N)) throw new Exception("invalid option.limit value");
  if ($N>999) $N=999;
  $return_array = explode(",", mysql_real_escape_string($params["return"]));
  if (!$return_array[0]) { // for some reasons, escape doesn't always work (lost mysql connection?)
     $return_array = array ("sort_name","email");
   }
  $fields = civicrm_api("contact","getfields",array("version"=>3));
  unset($fields["value"]["api_key"]); // security blacklist
  unset($fields["value"]["id"]); // security blacklist
  $fields = $fields['values'];
  $fields["email"]=1;
  foreach ($return_array as $k => &$v) {
    if (!array_key_exists($v,$fields))
       unset($return_array[$k]);
  }
  require_once 'CRM/Contact/BAO/Contact/Permission.php';
  list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('civicrm_contact');

  if ($aclWhere) {
    $where = " AND $aclWhere ";
  }


  $return = "civicrm_contact.id, email, ". implode (",",$return_array);
  if (!$params['with_email_only'] &&  strlen ($name) < $params['fastSearchLimit']) { // start with a quick and dirty

    $sql = "SELECT civicrm_contact.id, sort_name FROM civicrm_contact $aclFrom WHERE $aclWhere AND sort_name LIKE '$name%' ORDER BY sort_name LIMIT $N";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->N == $N) { 
      while($dao->fetch()) {
        $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name);
      }
      return civicrm_api3_create_success($result, $params, 'Contact', 'getgoodquick');
    }
  }
  // search on name and first name
  if ($params['with_email_only']) 
    $join = "JOIN";
  else 
    $join = "LEFT JOIN";

  $sql = "
    SELECT $return 
    FROM civicrm_contact $aclFrom
    $join civicrm_email ON civicrm_email.contact_id = civicrm_contact.id 
    WHERE (first_name LIKE '$name%' OR sort_name LIKE '$name%')
    AND $aclWhere 
    ORDER BY sort_name LIMIT $N";
  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()) {
    $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name);
    foreach ($return_array as $r)
      if (!empty($dao->$r)) 
        $result[$dao->id][$r] = $dao->$r;
  }

 // if matches found less than 15, try to find more 
  if($dao->N < $N) { 
    $limit = $N - $dao->N;
    if (strpos ($name," ") === false) {
      // find the match from email table 
      $sql = " 
        SELECT $return 
        FROM civicrm_email, civicrm_contact $aclFrom
        WHERE email LIKE '$name%' 
        AND civicrm_email.contact_id = civicrm_contact.id
        AND $aclWhere 
        ORDER BY sort_name  
        LIMIT $limit"; 
      } else {
        $names= explode (" ", $name);
        if (count($names)>2) {
          $where = " WHERE display_name LIKE '%$name%'";
        } else {
          $where = " WHERE sort_name LIKE '{$names[0]}, {$names[1]}%' OR sort_name LIKE '{$names[1]}%, {$names[0]}' ";
        }
        $sql = " 
          SELECT $return 
          FROM civicrm_contact $aclFrom
          $join civicrm_email ON civicrm_email.contact_id = civicrm_contact.id 
          $where  
          AND $aclWhere 
          ORDER BY sort_name  
          LIMIT $limit"; 
      }
      $dao = CRM_Core_DAO::executeQuery($sql); 
    while($dao->fetch()) {
      $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name);
      foreach ($return_array as $r)
        if (!empty($dao->$r)) 
          $result[$dao->id][$r] = $dao->$r;
    }
  }
  if (count ($result)<$N) { // scrapping the %bottom%
    $sql = "
      SELECT $return 
      FROM civicrm_contact $aclFrom
      LEFT JOIN civicrm_email ON civicrm_email.contact_id = civicrm_contact.id 
      WHERE (sort_name LIKE '%$name%')
      AND $aclWhere 
      ORDER BY sort_name LIMIT ". ($N - count ($result));
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name);
      foreach ($return_array as $r)
        if (!empty($dao->$r)) 
          $result[$dao->id][$r] = $dao->$r;
    }
  }
 
  if (count ($result)<$N && (strpos($name,".") !== false || strpos($name,"@"))) {
    // fetching on %email% if the string contains @ or . 
    $sql = "
      SELECT $return 
      FROM civicrm_contact $aclFrom
      LEFT JOIN civicrm_email ON civicrm_email.contact_id = civicrm_contact.id 
      WHERE (email LIKE '%$name%')
      AND $aclWhere 
      ORDER BY sort_name LIMIT ". ($N - count ($result));
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $result[$dao->id] = array (id=>$dao->id, "sort_name"=>$dao->sort_name);
      foreach ($return_array as $r)
        if (!empty($dao->$r)) 
          $result[$dao->id][$r] = $dao->$r;
    }

  }

  return civicrm_api3_create_success($result, $params, 'Contact', 'getttpquick');
}

