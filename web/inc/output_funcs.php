<?php

// output func to draw pics in grid (1, 2, 3 or 4 cols)

function draw_pic_fullwidth($dotgne_image){
  if ($dotgne_image['f_url'] === NULL ){
    // old flickr photo, imported
    $dotgne_remote_name = $dotgne_image['iid'].'.jpg';
  }
  else {
    // standard - file name in f_url
    $dotgne_remote_name = $dotgne_image['iuser'].'/'.$dotgne_image['f_url'];
  }
  $dotgne_thumb_url = getImgix($dotgne_remote_name,960);
  return('<p><img src="'.$dotgne_thumb_url.'" class="u-full-width fwpic"></p>');
}

function output_pic_list($db_resource,$cols,$view_page){
  $counter = 1;
  $use_cols = 12/$cols;
  switch($use_cols){
    case 2:
      $use_cols_text = 'two';
      break;
    case 3:
      $use_cols_text = 'three';
      break;
    case 4:
      $use_cols_text = 'four';
      break;
    case 6:
      $use_cols_text = 'six';
      break;
    case 12:
      $use_cols_text = 'twelve';
      break;
  };
  while($dotgne_lister_pic = pg_fetch_array($db_resource)){
    // if 1, start row
    if ($counter == 1){
      start_skel_row('');
    };
    // start unit
    start_skel_unit($use_cols_text);
    // draw image
      echo(draw_pic_fullwidth($dotgne_lister_pic));
      $dotgne_view_url = getenv('BASE_URL').'photo/'.$dotgne_lister_pic['iuser'].'/'.$dotgne_lister_pic['iid'].'/'.$view_page.'/';
      echo('<h4>'.$dotgne_lister_pic['title'].'</h4>');
      echo('<p><a href="'.$dotgne_view_url.'">View</a></p>');
    // close unit
    end_skel_unit();
    // if $cols th unit, close row, reset counter
    if ($counter == $cols){
      end_skel_row();
      $counter = 1;
    }
    else {
      $counter++;
    };
  }; // while loop
}; // func

function output_single_pic($db_resource){
  // error_log('output single func');
  start_skel_row('m50top');
  start_skel_unit('twelve');
  while($dotgne_lister_pic = pg_fetch_array($db_resource)){
    echo(draw_pic_fullwidth($dotgne_lister_pic));
    echo('<h4>'.$dotgne_lister_pic['title'].'</h4>');
    if ($dotgne_lister_pic['description'] != ''){
      echo('<p class="picdesc">'.$dotgne_lister_pic['description'].'</p>');
    }
    if ($dotgne_lister_pic['datetaken'] != ''){
      $out_date = date("F j, Y",strtotime($dotgne_lister_pic['datetaken']));  
      echo('<p class="picdate">'.$out_date.'</p>');
    }
    else {
      echo('<!-- date not known -->');
    }
    
  };
  end_skel_unit();
  end_skel_row();
};

function output_menu(){
  // draw top menu in 12col
  global $dotgne_logged_in;
  global $dotgne_acc; // acc being viewed
  global $viewer_acc; // the acc viewing
  start_skel_row_12('topmenu');
  if ($dotgne_logged_in == 1){
    echo('<a href="'.getenv('BASE_URL').'user/'.$viewer_acc.'/1/" class="">Your Photos</a>');
    echo(' | <a href="'.getenv('BASE_URL').'upload/" class="">Upload</a>');
    echo(' | <a href="'.getenv('BASE_URL').'privacy/" class="">Privacy</a>');
    echo(' | <a href="'.getenv('BASE_URL').'logout/" class="">Log Out</a>');
  }
  else {
    echo('<a href="'.getenv('BASE_URL').'login/" class="">Log In</a>');
  }
  end_skel_row_12();
}

function output_pagination(){
  // draw the pagination links
  global $dotgne_logged_in;
  global $dotgne_view_level;
  global $dotgne_acc; // acc being viewed
  global $viewer_acc; // the acc viewing
  global $dotgne_list_perpage;
  global $view_page;
  global $con;
  
  // output pagination
  $list_url_base = getenv('BASE_URL').'user/'.$dotgne_acc.'/';
  // work out total pages
  $pq1 = 'SELECT COUNT(*) FROM dotgne.photos WHERE "iuser" = '.pg_escape_literal($dotgne_acc).'
  AND "privacy" <= '.pg_escape_literal($dotgne_view_level);
  $rs1 = pg_query($con, $pq1);
  $total_pics_in_view = pg_fetch_array($rs1);
  $total_pages = ceil($total_pics_in_view['count']/$dotgne_list_perpage);
  $paginator_pages = array(); // array to hold pages to link to
  $pages_around_current = 3;
  array_push($paginator_pages,1); // p1
  // count down n from current
  for ($n = $pages_around_current; $n >= 1 ; $n--){
   if (($view_page - $n) > 1){
     array_push($paginator_pages,($view_page - $n));
   }
  };
  // current and count up n from current
  for ($n = 0; $n <= $pages_around_current ; $n++){
    if ((($view_page + $n) < $total_pages) && (($view_page + $n) > 1)){
      array_push($paginator_pages,($view_page + $n));
    }
  } 
  array_push($paginator_pages,$total_pages); // last
  // output that list
  start_skel_row_12('btmpagination');
  $last_paginator_page = 0;
  foreach ($paginator_pages as $paginator_page){
    if ($paginator_page != ($last_paginator_page + 1)){
      echo('<span class="paginator"> ... </span>');
    };
    if ($paginator_page == $view_page){
      echo('<span class="paginator currentpaginate">'.$paginator_page.'</span>');
    }
    else {
      echo('<span class="paginator"><a href="'.$list_url_base.$paginator_page.'/">'.$paginator_page.'</a></span>');
    };
    $last_paginator_page = $paginator_page;
  };
  end_skel_row_12(); 
}

function draw_privacy_options($level){
  $selected_priv = array();
  $selected_priv[$level] = 'selected';
  ?>
  <option value="0" <?=$selected_priv[0];?>>Anyone</option>
  <option value="1" <?=$selected_priv[1];?>>Friends and Family</option>
  <option value="2" <?=$selected_priv[2];?>>Family Only</option>
  <option value="3" <?=$selected_priv[3];?>>Only Me</option>
  <?php
}

function draw_privacy_options_user($level,$min){
  $selected_priv = array();
  $selected_priv[$level] = 'selected';
  if ($min == 0){
    echo('<option value="0" '.$selected_priv[0].'>None</option>');
  };
  ?>
  <option value="1" <?=$selected_priv[1];?>>Friend</option>
  <option value="2" <?=$selected_priv[2];?>>Family</option>

  <?php
}

?>
