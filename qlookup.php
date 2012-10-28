<?php

require_once 'qlookup.civix.php';
/**
 * Implementation of hook_civicrm_config
 */
function qlookup_civicrm_config(&$config) {
  _qlookup_civix_civicrm_config($config);
}

/**
 * Alter fields for an event registration to make them into a demo form.
 */
function qlookup_civicrm_alterContent( &$content, $context, $tplName, &$object ) {
//print_r($object);
//die ($context);
  $pos = strpos($content, "#sort_name_navigation");
  if ($pos === false) {
    return;//no navigation menu
  }

$str = <<<'EOD'
<script>
cj (function($) {
  $("#sort_name_navigation").crmAutocomplete({action:"getgoodquick",selectFirst:false,autoField:false}, {
    formatItem:function(data,i,max,value,term) {
      if (typeof data["email"] != "undefined")
        return data["sort_name"] + " : " + data["email"];
      else
        return data["sort_name"];
    },
    result:function(data){
      if (data && data.id) {
        document.location="/civicrm/contact/view?reset=1&cid="+data.id;
      }
    }  
  });
});
</script>
EOD;
  $content = str_replace ("#sort_name_navigation","#disabled_by_qloockup",$content).$str;
}

function  qlookup_civicrm_contactListQuery ( &$query, $name, $context, $id ) {
  //@TODO
  // search on the email if name contains @
  // if name contains a space, split, sort the word(s) by length and select smaller from select longer 
  // if nb words >2, skip the smallest
  if ($context == 'navigation') {
    $limit=11;
    $fastSearchLimit = 3;
    // ideally, the hook isn't on the query, but on the result, but that will be trivial to do when moving the autocomplete to the api
    // @TODO if the search result < 11 (the limit of the autocomplete, replace the query by LIKE '%name%)
    if (strlen ($name) > $fastSearchLimit) {
      $query = "(SELECT c.sort_name as data, c.id FROM civicrm_contact c WHERE is_deleted=0 AND c.sort_name LIKE '$name%' OR 
       c.first_name LIKE '$name%' ORDER BY sort_name limit $limit ) UNION 
       (SELECT c.sort_name as data, c.id FROM civicrm_contact c, civicrm_email m where  c.is_deleted=0 AND c.id = m.contact_id AND email like '$name%' ORDER BY sort_name limit $limit )";
      //feels like it works better by searching on all emails instead of only the primary one. IMMV
    } else { // we assume that more than 25 will come up on the first or last name before having to dig on the email too (union are expensive)
      $query = "SELECT c.sort_name as data, c.id FROM civicrm_contact c WHERE is_deleted=0 AND c.sort_name LIKE '$name%' OR c.first_name LIKE '$name%' ORDER BY sort_name LIMIT ".$limit;
    }
  }
}
