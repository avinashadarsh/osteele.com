<?php
/*
	Support script for the Extended Live Archive WordPress Plugin.
*/

/*	Extended Live Archives is extensively based on code from Jonas Rabbe and his 
	Super Archives Plugin. This is merely an extension to this already existing 
	wonderful plugin (see 
	http://www.jonas.rabbe.com/archives/2005/05/08/super-archives-plugin-for-wordpress/)
	for more info.
	
	Copyright 2005  Arnaud Froment 
	Copyright 2005  Jonas Rabbe  (email : jonas@rabbe.com)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require('../../../../wp-blog-header.php');

function af_ela_truncate_title($title) {
	global $settings;
	if( $settings['truncate_title_length'] > 0 ) {
		if( strlen($title) > $settings['truncate_title_length'] ) {
			$title = substr($title, 0, $settings['truncate_title_length']);
			if( $settings['truncate_title_at_space'] == 1 ) {
				$pos = strrpos($title, ' ');
				if( $pos !== false ) {
						$title = substr($title, 0, $pos);
				}
			}
			$title .= $settings['truncate_title_text'];
		}
	}
	return $title;
}

function af_ela_truncate_cat_title($title) {
	global $settings;
	if( $settings['truncate_cat_length'] > 0 ) {
		if( strlen($title) > $settings['truncate_cat_length'] ) {
			$title = substr($title, 0, $settings['truncate_cat_length']);
			if( $settings['truncate_title_at_space'] == 1 ) {
				$pos = strrpos($title, ' ');
				if( $pos !== false ) {
						$title = substr($title, 0, $pos);
				}
			}
			$title .= $settings['truncate_title_text'];
		}
	}
	return $title;
}

function af_ela_read_years()  {
	global $year, $years, $path, $settings; 	
	$year_contents = @file_get_contents($path . 'years.dat');
	if( $year_contents === false ) $year_contents = '';
	
	$years = unserialize($year_contents);
	if( $years === false ) {
		echo "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file for years</p>";
		return FALSE;
	}
	
	if( $settings['newest_first'] == 0 ) {
		$years = array_reverse($years, true);
	}
	
	if( !array_key_exists($year, $years) ) {
		$temp = array_keys($years);
		$year = $temp[0];
	}
	return TRUE;
}
	
function af_ela_read_months()  {
	global $month, $months, $year, $years, $path, $settings; 	
	$month_contents = @file_get_contents($path . $year . '.dat');
	if( $month_contents === false ) $month_contents = '';
	
	$months = unserialize($month_contents);
	if( $months === false ) {
		echo "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file '$year.dat'</p>";
		return FALSE;;
	}

	if( $settings['newest_first'] == 0 ) {
		$months = array_reverse($months, true);
	}
	
	if( !array_key_exists($month, $months) ) {
		$temp = array_keys($months);
		$month = $temp[0];
	}
	return TRUE;
}

function af_ela_read_categories() {
	global $category, $categories, $path, $settings;
	$category_contents = @file_get_contents($path . 'categories.dat');
	if( $category_contents === false ) $category_contents = '';
	
	$categories = unserialize($category_contents);
	if( $categories === false ) {
		echo "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file for categories</p>";
		return FALSE;
	}
	return TRUE;
}

function af_ela_read_tags() {
	global $tag, $tags, $path, $settings;
	$tag_contents = @file_get_contents($path . 'tags.dat');
	if( $tag_contents === false ) $tag_contents = '';
	
	$tags = unserialize($tag_contents);
	if( $tags === false ) {
		echo "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file for tags</p>";
		return FALSE;
	}
	return TRUE;
}

function af_ela_read_posts() {
	global $month, $months, $year, $years, $category, $categories, $tag, $tags, $posts, $path, $settings, $menu_table, $menu; 
	switch($menu_table[$menu]) {
	case 'chrono':
		$post_contents = @file_get_contents($path . $year . '-' . $month . '.dat');
		$message = "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file '$year-$month.dat'</p>";
		break;
	case 'cats':
		$post_contents = @file_get_contents($path . 'cat-' . $category. '.dat');
		if( $post_contents === false ) {
			$keys = array_keys($categories);
			$i=0;
			while (!$categories[$keys[$i++]][3]) $category = $categories[$keys[$i]][0];
			$post_contents = @file_get_contents($path . 'cat-' . $category. '.dat');
		}
		$message = "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file 'cat-$category.dat'</p>";
		break;
	case 'tags':
		$post_contents = @file_get_contents($path . 'tag-' . $tag . '.dat');
		if( $post_contents === false ) {	
			$keys = array_keys($tags);
			$tag = $tags[$keys[0]][0];
			$post_contents = @file_get_contents($path . 'tag-' . $tag . '.dat');
		}
		$message =  "${settings['id']}|<p class='${settings['error_class']}'>Could not open cache file 'tag-$tag.dat'</p>";
	default:
		break;
	}
	if( $post_contents === false ) $post_contents = '';
	$posts = unserialize($post_contents);
	if( $posts === false ) {
		echo $message;
		return FALSE;
	}
	if( $settings['newest_first'] == 0 ) {
		$posts = array_reverse($posts, true);
	}
	return TRUE;
}

function af_ela_generate_years() {
	global $year, $years, $settings;
	$year_list = '';
	foreach( $years as $y => $p ) {
		$current = '';
		$current_text = '';
		if( $y == $year ) {
			$current = ' class="'.$settings['selected_class'].'"';
			$current_text = $settings['selected_text'] == '' ? '' : ' ' . $settings['selected_text'];
		}
		
		if( $settings['num_entries'] == 1 ) {
			$num = ' ' . str_replace('%', $p, $settings['number_text']);
			}
			
			$year_list .= <<<END_TEXT
<li id="${settings['id']}-year-$y"$current>$y$num$current_text</li>

END_TEXT;
		}
		$year_list = <<<END_LIST
<ul id="${settings['id']}-year"$fade_year>
$year_list</ul>
END_LIST;

	return $year_list;
}

function af_ela_generate_months() {
	global $month, $months, $month_names, $settings;
	$month_list = '';
	foreach( $months as $m => $p ) {
		$current = '';
		$current_text = '';
		if( $m == $month ) {
			$current = ' class="' . $settings['selected_class'] . '"';
			$current_text = $settings['selected_text'] == '' ? '' : ' ' . $settings['selected_text'];
		}
		
		if( $settings['num_entries'] == 1 ) {
			$num = ' ' . str_replace('%', $p, $settings['number_text']);
			}
			
			$n = $month_names[$m];
			$month_list .= <<<END_TEXT
<li id="${settings['id']}-month-$m"$current>$n$num$current_text</li>

END_TEXT;
		}
		$month_list = <<<END_LIST
<ul id="${settings['id']}-month"$fade_month>
$month_list</ul>
END_LIST;

	return $month_list;
}

function af_ela_generate_categories() {	
	global $category, $categories, $settings;
	$category_list = '';
	foreach( $categories as $c => $p ) {
		if ($p[6]) {
			if (!isset($p[3])) {
				$current = ' class="empty"';
			} else {
				$current = '';
			}
				
			if( $p[0] == $category ) {
				$current = ' class="'.$settings['selected_class'].'"';
				$current_text = $settings['selected_text'] == '' ? '' : ' ' . $settings['selected_text'];
			} else {
				$current_text = '';
			}
			
			if( $settings['num_entries'] == 1 ) {
				$num = ' ' . str_replace('%', $p[3], $settings['number_text']);
			}
			
			// 	truncate titles
			$title = af_ela_truncate_cat_title($p[1]);
	
			// Add stuff if working on a child
			$before_children ='';
			$after_children  ='';
			if ($p[5] != 0) {
				for ($i = 1; $i <intval($p[5]); $i++) {
					$before_children .=$settings['before_child'];
					$after_children  .=$settings['after_child'];
					}
				}
				$category_list .= <<<END_TEXT
<li id="${settings['id']}-category-$p[0]"$current>$before_children$title$num$current_text$after_children</li>

END_TEXT;
			}
		}
		$category_list = <<<END_LIST
<ul id="${settings['id']}-category"$fade_category>
$category_list</ul>
END_LIST;

	return $category_list;
}

function af_ela_generate_tags() {
	global $tag, $tags, $settings;
	$tag_list = '';
	$tagged_posts = $tags[0][0];
	$posted_tags = $tags[0][1];
	foreach( $tags as $t => $p ) {
		if ($p[2]) {
			if( $p[0] == $tag ) {
				$current = ' class="'.$settings['selected_class'].'"';
				$current_text = $settings['selected_text'] == '' ? '' : ' ' . $settings['selected_text'];
			} else {
				$current = '';
				$current_text = '';
			}
			if( $settings['num_entries_tagged'] == 1 ) {
				$num = ' ' . str_replace('%', $p[2], $settings['number_text_tagged']);
			}
			$tag_weight = $p[2] / $posted_tags * 100;
			$utwClass = new UltimateTagWarriorCore;
			$tag_weightcolor = $utwClass->GetColorForWeight($tag_weight);
			$tag_weightfontsize = $utwClass->GetFontSizeForWeight($tag_weight);
			
			$tag_list .= <<<END_TEXT
<li id="${settings['id']}-tag-$p[0]"$current> <font style="font-size: $tag_weightfontsize !important; color: $tag_weightcolor !important">$p[1]$num$current_text</font> </li> 

END_TEXT;
			}
		}
		$tag_list = <<<END_LIST
<ul id="${settings['id']}-tag"$fade_tag>
$tag_list</ul>
END_LIST;
	
	return $tag_list;
}

function af_ela_generate_posts() {
	global $posts, $post, $settings, $menu_table, $menu,$category; 
	$post_list = '';
	
	foreach( $posts as $d => $p ) {
		if( $settings['num_comments'] == 1 ) {
			if( $p[4] == 'closed' ) {
				$cmt_text = ' ' . str_replace('%', $p[3], $settings['closed_comment_text']);
			} else {
				$cmt_text = ' ' . str_replace('%', $p[3], $settings['comment_text']);
			}
		}
		
		if( $settings['day_format'] == '' ) {
			$day = '';
		} else {
			$day = date($settings['day_format'], strtotime("$year-$month-${p[0]}")) . ' ';
		}
				
		$title = af_ela_truncate_title($p[1]);

		$num_treated_posts = 0;
		
		switch($menu_table[$menu]) {
		case 'cats':
			$num_treated_posts++;
			if ($num_treated_posts >= $settings['num_posts_by_cat'] && $settings['num_posts_by_cat'] != 0) {
				$catlink = get_category_link($category);
				$cat_more_text = sprintf(__($settings['cat_more_text']), get_the_category_by_ID($category));
				$post_in_cat_list .= <<<END_TEXT
<li id='${settings['id']}-post-more'><a href='$catlink'>$cat_more_text&#8230;</a></li>
END_TEXT;
				}
			$post_in_menu = "-cats";
			break;
	
		case 'tags':
			$post_in_menu = "-tags";
			break;
			
		case 'chrono':
			$post_in_menu = "-chrono";
		default:
			break;	
		}
		
		$post_list .= <<<END_TEXT
<li id='${settings['id']}-post-${p[0]}'>$day<a href='${p[2]}'>$title</a>$cmt_text</li>

END_TEXT;
	}
		
	$post_list = <<<END_LIST
<ul id="${settings['id']}-post$post_in_menu"$fade_post>
$post_list</ul>
END_LIST;
	
	return $post_list;
}


// get the year that is requested, if no year is requested set the year to zer0
$menu = isset($_REQUEST['menu']) ? $_REQUEST['menu'] : 0;
$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : 0;
$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : 0;
$category = isset($_REQUEST['category']) ? $_REQUEST['category'] : 1;
$tag = isset($_REQUEST['tag']) ? $_REQUEST['tag'] : 0;

// the paths for the cache files and settings
$path = ABSPATH . 'wp-content/af-extended-live-archive/';

// get settings and construct default;
$settings = get_option('af_ela_options');
$is_initialized = get_option('af_ela_is_initialized');
if (!$settings || !$is_initialized || strstr($settings['installed_version'], $is_initialized) === false ) {
		echo '<div id="af-ela"><p class="alert">Plugin is not initialized. Admin or blog owner, <a href="' . get_settings('home') . '/wp-admin/options-general.php?page=af-extended-live-archive/af-extended-live-archive-options.php">visit the ELA option panel</a> in your admin section.</p></div>';
		die();
}

$settings['loading_content'] = urldecode($settings['loading_content']);
$settings['idle_content'] = urldecode($settings['idle_content']);
$settings['selected_text'] = urldecode($settings['selected_text']);
$settings['truncate_title_text'] = urldecode($settings['truncate_title_text']);
$settings['loading_content'] = stripslashes($settings['loading_content']);
$settings['idle_content'] = stripslashes($settings['idle_content']);
	
$menu_headers['chrono'] = $settings['menu_month'];
$menu_headers['cats'] = $settings['menu_cat'];
$menu_headers['tags'] = $settings['menu_tag'];
	
if (!empty($settings['menu_order'])) {
	$menu_table = preg_split('/[\s,]+/',$settings['menu_order']);
}

$fade_year = ' ';
$fade_month = ' ';
$fade_post = ' ';
$fade_category = ' ';
$fade_tag = ' ';
	
// if fade is set, check the requested year and month
if( $settings['fade'] == 1 ) {
	if ($tag == 0 ) {
		$fade_tag = ' class="fade"';
	} elseif ($category == 0 ) {
		$fade_category = ' class="fade"';
	} elseif( $year == 0 && $month == 0 ) {
		$fade_year = ' class="fade"';
		$fade_month = ' class="fade"';
		$fade_post = ' class="fade"';
	} elseif( $month == 0 ) {
		$fade_month = ' class="fade"';
		$fade_post = ' class="fade"';
	} else {
		$fade_post = ' class="fade"';
	}
}

// Output charset header
header("Content-Type: text/html; charset=${settings['charset']}");
	$text = <<<HEADER_TEXT
<div id="${settings['id']}-loading">${settings['idle_content']}</div>
<ul id="${settings['id']}-menu">

HEADER_TEXT;

	
	foreach($menu_table as $menu_key => $menu_item) {
		if ($menu_item !='none') {
		if ($menu_key == $menu) {
			$current = ' class="'.$settings['selected_class'].'"';
		} else {
			$current = '';
			}
			$text .= <<<BEGIN_TEXT
<li id="${settings['id']}-menu-$menu_key"$current>${menu_headers[$menu_item]}</li>

BEGIN_TEXT;
		}
	}
	$text .= "</ul>";

switch($menu_table[$menu]) {
case 'chrono':

	$err = af_ela_read_years();
	if ($err === false) die();
	$err = af_ela_read_months();
	if ($err === false) die();
	$err = af_ela_read_posts();
	if ($err === false) die();
	$year_list = af_ela_generate_years();
		
	$month_names = array('', __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December'));
	
	$month_list = af_ela_generate_months();
			
	$post_list = af_ela_generate_posts();
		
	$text .= $year_list . $month_list . $post_list;
	break;
		
case 'cats':	

	$err = af_ela_read_categories();
	if ($err === false) die();
	$err = af_ela_read_posts();
	if ($err === false) die();
	$category_list = af_ela_generate_categories();

	$post_list = af_ela_generate_posts();	
		
	$text .= $category_list . $post_list;
	break;
		
case 'tags':	

	$err = af_ela_read_tags();	
	if ($err === false) die();
	$err = af_ela_read_posts();
	if ($err === false) die();
	$tag_list = af_ela_generate_tags();	
		
	$post_list = af_ela_generate_posts();
	
	$text .= $tag_list . $post_list;
	
	break;
		
case 'none':
default:
	break;
}

echo ' ' .$settings['id'] . '|';
echo $text;
?>