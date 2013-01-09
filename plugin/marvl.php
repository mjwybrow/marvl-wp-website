<?php
/*
 * Plugin Name: MArVL
 * Plugin URI: http://marvl.infotech.monash.edu.au
 * Description: Display and edit people and projects for Monash Adaptive Visualisation Lab.
 * Version: 0.1
 * Author: Michael Wybrow
 * Author URI: http://www.csse.monash.edu.au/~mwybrow
 * */

/*
Copyright (c) 2013, Monash University.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, 
MA  02110-1301, USA.
*/


$marvl_db_version = "0.1";

function marvl_install ()
{
    global $wpdb;
    global $jal_db_version;

    $table_name = $wpdb->prefix . "marvl_members";
    if ($wpdb->get_var("show tables like '$table_name'") != $table_name)
    {
          
/*          $sql = "CREATE TABLE " . $table_name . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time bigint(11) DEFAULT '0' NOT NULL,
      name tinytext NOT NULL,
      text text NOT NULL,
      url VARCHAR(55) NOT NULL,
      UNIQUE KEY id (id)
    );";

          require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
          dbDelta($sql);
*/
          $welcome_name = "Mr. Wordpress";
          $welcome_text = "Congratulations, you just completed the installation!";
/*
          $insert = "INSERT INTO " . $table_name .
                " (time, name, text) " .
                "VALUES ('" . time() . "','" . $wpdb->escape($welcome_name) . "','" . $wpdb->escape($welcome_text) . "')";

          $results = $wpdb->query( $insert );
*/ 
          add_option("marvl_db_version", $marvl_db_version);
    }
}


class marvl_Options
{
    var $member_id;
    var $project_id;
    var $software_id;

    function reset_query()
    {
        $this->member_id = 0;
        $this->project_id = 0;
        $this->software_id = 0;
    }

    function marvl_Options()
    {
        $this->reset_query();
    }
}

$marvlOpts = new marvl_Options();

function marvl_filter_query_vars($wpvarstoreset)
{
    $wpvarstoreset[]='marvl_member';
    $wpvarstoreset[]='marvl_project';
    $wpvarstoreset[]='marvl_software';
    return $wpvarstoreset;
}


function marvl_filter_parse_query($wp_query)
{
    global $marvlOpts;
    // query_posts() can be called multiple times. So reset all our variables.
    $marvlOpts->reset_query();
    // Deal with marvlOpts-specific parameters.
    if( !empty($wp_query->query_vars['marvl_member']) )
    {
        $marvlOpts->member_id = intval($wp_query->query_vars['marvl_member']);
    }
    if( !empty($wp_query->query_vars['marvl_project']) )
    {
        $marvlOpts->project_id = intval($wp_query->query_vars['marvl_project']);
    }
    if( !empty($wp_query->query_vars['marvl_software']) )
    {
        $marvlOpts->software_id = intval($wp_query->query_vars['marvl_software']);
    }
}

function marvl_show_project_list()
{
    global $wpdb;
    $output = "";

    $projects = $wpdb->get_results( "SELECT *
	    FROM marvl_projects ORDER BY project_order");

    foreach($projects as $project)
    {
	$output .= "<a class=\"marvl_object_link\" href=\"?marvl_project={$project->project_id}\"><div class=\"marvl_software\">\n";
        $output .= "<h2>{$project->project_title}</h2>\n";
        $output .= "{$project->project_description_intro}\n";
        $output .= "</div></a>\n";
    }

    return $output;
}

function marvl_show_project($project_id)
{
    global $wpdb;
    $output = "";
    
    $projects = $wpdb->get_results( "SELECT *
            FROM marvl_projects WHERE project_id = {$project_id}");

    foreach($projects as $project)
    {
        $output .= "<div class=\"marvl_software\">\n";
        $output .= "<h2>{$project->project_title}</h2>\n";
        $output .= "{$project->project_description_intro}\n";
        $output .= "{$project->project_description}\n";
        $output .= "</div>\n";
        if (strlen($project->project_url) > 0)
        {
            $output .= "<p>Website: <a href=\"{$project->project_url}\">{$project->project_url}</a></p>\n";
        }
    }

    return $output;
}


function marvl_show_software_list()
{
    global $wpdb;
    $output = "";
    
    $softwares = $wpdb->get_results( "SELECT *
            FROM marvl_software ORDER BY software_order");

    foreach($softwares as $software)
    {
        $output .= "<a class=\"marvl_object_link\" href=\"?marvl_software={$software->software_id}\"><div class=\"marvl_software\">\n";
        $output .= "<h2>{$software->software_title}</h2>\n";
        $output .= "{$software->software_description_intro}\n";
        $output .= "</div></a>\n";
    }

    return $output;
}

function marvl_show_software($software_id)
{
    global $wpdb;
    $output = "";
    
    $softwares = $wpdb->get_results( "SELECT *
            FROM marvl_software WHERE software_id = {$software_id}");

    foreach($softwares as $software)
    {
        $output .= "<div class=\"marvl_software\">\n";
        $output .= "<h2>{$software->software_title}</h2>\n";
        $output .= "{$software->software_description_intro}\n";
        $output .= "{$software->software_description}\n";
        $output .= "</div>\n";
        if (strlen($software->software_url) > 0)
        {
            $output .= "<p>Website: <a href=\"{$software->software_url}\">{$software->software_url}</a></p>\n";
        }
    }

    return $output;
}

function marvl_show_member_list()
{
    global $wpdb;
    $output = "";
    
    $members = $wpdb->get_results( "SELECT *
            FROM marvl_members ORDER BY member_order");

    foreach($members as $member)
    {
        $output .= "<a class=\"marvl_object_link\" href=\"?marvl_member={$member->member_id}\"><div class=\"marvl_member\" style=\"background-image: url({$member->member_image_url});\">\n";
        $output .= "<h2>{$member->member_title}</h2>\n";
        $output .= "<h3>{$member->member_position}</h3>\n";
        $output .= "{$member->member_interests}\n";
        $output .= "</div></a>\n";
    }

    return $output;
}

function marvl_show_member($member_id)
{
    global $wpdb;
    $output = "";
    
    $members = $wpdb->get_results( "SELECT *
            FROM marvl_members WHERE member_id = {$member_id}");

    foreach($members as $member)
    {
        $output .= "<div class=\"marvl_member\" style=\"background-image: url({$member->member_image_url});\">\n";
        $output .= "<h2>{$member->member_position}</h2>\n";
        $output .= "<h3>{$member->member_affiliation}</h3>\n";
        $output .= "{$member->member_interests}\n";
        $output .= "</div>\n";
        $output .= "{$member->member_bio}\n";
        if (strlen($member->member_web_url) > 0)
        {
            $output .= "<p>Website: <a href=\"{$member->member_web_url}\">{$member->member_web_url}</a></p>\n";
        }
    }

    return $output;
}



function marvl_the_content($content) 
{
    global $marvlOpts;

    if (strstr($content, "<!-- marvl-" ))
    {
        $args = explode(" ", $content);
        $instruction = $args[1];
        $argument = $args[2];
        $output = "";

        if ($instruction == "marvl-members")
        {
            if ($marvlOpts->member_id > 0)
            {
                $output = marvl_show_member($marvlOpts->member_id); 
            }
            else
            {
                $output = marvl_show_member_list(); 
            }
        }
        elseif ($instruction == "marvl-software")
        {
            if ($marvlOpts->software_id > 0)
            {
                $output = marvl_show_software($marvlOpts->software_id); 
            }
            else
            {
                $output = marvl_show_software_list(); 
            }
        }
	elseif ($instruction == "marvl-projects")
	{
	    if ($marvlOpts->project_id > 0)
	    {
		$output = marvl_show_project($marvlOpts->project_id);
	    }
	    else
	    {
		$output = marvl_show_project_list();
	    }
	}

        if (strlen($output) > 0)
        {
            $content = preg_replace("/<p>\s*<!-- marvl-[a-z\- 0-9]* -->\s*<\/p>/", $output, $content);
        }
    }
    return $content; 
}


// Make it look like there are child pages for Software, Members, etc.
function marvl_pages_items($items)
{
    global $wpdb, $marvlOpts;
    
    // Create sub-page items for members.
    $submenu = "";
    $members = $wpdb->get_results( "SELECT *
            FROM marvl_members ORDER BY member_order");

    foreach($members as $member)
    {
        $name = preg_replace("/Prof\. /", "", $member->member_title, 1);
        $name = preg_replace("/Dr\. /", "", $name, 1);

        $extra = "";
        if ($marvlOpts->member_id == $member->member_id)
        {
            // This is the current page.
            $extra = " current_page_item";
        }
        $submenu .= "<li class=\"page_item{$extra}\"><a href=\"/members/?marvl_member={$member->member_id}\">{$name}</a></li>";
    }
    if ($submenu != "")
    {
        // There are some children
        $submenu = "<ul class='children'>" . $submenu . "</ul>";
        $items = preg_replace("/(<a href=\"http:\/\/marvl.infotech.monash.edu.au\/members\/\">Members<\/a>)/", "\\1{$submenu}", $items);
    }
    
    
    // Create sub-page items for software.
    $submenu = "";
    $softwares = $wpdb->get_results( "SELECT *
            FROM marvl_software ORDER BY software_order");

    foreach($softwares as $software)
    {
        $extra = "";
        if ($marvlOpts->software_id == $software->software_id)
        {
            // This is the current page.
            $extra = " current_page_item";
        }
        $submenu .= "<li class=\"page_item{$extra}\"><a href=\"/software/?marvl_software={$software->software_id}\">{$software->software_title}</a></li>";
    }
    if ($submenu != "")
    {
        // There are some children
        $submenu = "<ul class='children'>" . $submenu . "</ul>";
        $items = preg_replace("/(<a href=\"http:\/\/marvl.infotech.monash.edu.au\/software\/\">Software<\/a>)/", "\\1{$submenu}", $items);
    }

    // Create sub-page items for projects.
    $submenu = "";
    $projects = $wpdb->get_results( "SELECT *
            FROM marvl_projects WHERE project_state = 'active' ORDER BY project_order");
    
    foreach($projects as $project)
    {
        $extra = "";
	if ($marvlOpts->project_id == $project->project_id)
	{
	    // This is the current page.
	    $extra = " current_page_item";
	}
	$submenu .= "<li class=\"page_item{$extra}\"><a class=\"long_link\" href=\"/current-projects/?marvl_project={$project->project_id}\">{$project->project_title}</a></li>";
    }
    if ($submenu != "")
    {
	// There are some children.
	$submenu = "<ul class='children'>" . $submenu . "</ul>";
        $items = preg_replace("/(<a href=\"http:\/\/marvl.infotech.monash.edu.au\/current-projects\/\">Projects<\/a>)/", "\\1{$submenu}", $items);

    }

    return $items;
}


// We want to change just the page title at the top of the page.
function marvl_the_title($title, $id)
{
    global $wpdb;

    if( $title == "Members" && in_the_loop() )
    {
        // Work around the fact the_title is called for the titles of all 
        // pages in the menus, etc.  in_the_loop causes it not to change the
        // "Members" page title in the menus.
        $member_id = get_query_var('marvl_member');
        if (isset($member_id))
        {
            $members = $wpdb->get_results( "SELECT *
                    FROM marvl_members WHERE member_id = {$member_id}");

            if (count($members) == 1)
            {
                $title = $members[0]->member_title;
            }
        }
    }
    return $title;   
}



add_filter('the_title', 'marvl_the_title', 10, 2); 
add_filter('wp_list_pages', 'marvl_pages_items');
add_filter('query_vars', 'marvl_filter_query_vars');
add_filter('parse_query', 'marvl_filter_parse_query');
add_filter('the_content', 'marvl_the_content'); 

add_action('activate_plugindir/pluginfile.php', 'marvl_install');




/* vim: ts=4 sw=4 et tw=0 wm=0
*/
?>
