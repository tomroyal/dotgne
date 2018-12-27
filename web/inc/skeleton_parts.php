<?php

// full width convenience funcs

function start_skel_row_12($add_class){
  // start a skeleton row with additional class if req
  echo('<div class="row">');
  echo('<div class="twelve columns '.$add_class.'">');
}

function end_skel_row_12(){
  echo('</div></div>');
}

// row

function start_skel_row($add_class){
  // start a skeleton row with additional class if req
  echo('<div class="row '.$add_class.'">');
}

function end_skel_row(){
  echo('</div>');
}

// in row unit

function start_skel_unit($cols){
  echo('<div class="'.$cols.' columns">');
}
function end_skel_unit(){
  echo('</div>');
}

?>
