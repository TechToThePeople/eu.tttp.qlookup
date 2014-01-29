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
  $pos = strpos($content, "#sort_name_navigation");
  if ($pos === false) {
    return;//no navigation menu
  }

  $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1')."&cid=";
  $str = 
<<<'EOD'
<style>
.ac_results span.email {display:block;float:right;padding_left:10px;}
</style>
<script>
(function($,url){
  $(function(){
    $(".crm-quickSearchField").closest("ul").remove();
    $("#sort_name_navigation").crmAutocomplete({action:"getttpquick"}, {
      minChars:3,
      width:450,
      formatItem:function(data,i,max,value,term) {
        if (typeof data["email"] != "undefined")
          return data["sort_name"] + "<span class='email'>" + data["email"]+"</span>";
        else
          return data["sort_name"];
      },
      result:function(data){
        if (data && data.id) {
          document.location=url+data.id;
        }
      }  
    });
    $('#id_search_block').submit (function() {
      var q=$("#sort_name_navigation").val();
      if (!isNaN(parseFloat(q)) && isFinite(q)) {
        document.location=url+q;
        return false;
      }
    });
  });
EOD;
  // wrapped in an anonymous fct that has cj and the contact view url as param
  $str = $str."\n})(cj,'$url');</script>";
  $content = str_replace ("#sort_name_navigation","#disabled_by_qloockup",$content).$str;
}

function  aaqlookup_civicrm_contactListQuery ( &$query, $name, $context, $id ) {
  //@TODO
  // search on the email if name contains @
  // if name contains a space, split, sort the word(s) by length and select smaller from select longer 
  // if nb words >2, skip the smallest
  if ($context == 'navigation') {
    $limit=11;

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
