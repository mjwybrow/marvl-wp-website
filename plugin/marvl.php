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
    var $project_state;
    var $software_id;

    function reset_query()
    {
        $this->member_id = 0;
        $this->project_id = 0;
        $this->project_state = 1; // default to 'active' state
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
    $wpvarstoreset[]='marvl_project_state';
    $wpvarstoreset[]='marvl_software';
    return $wpvarstoreset;
}


function marvl_filter_parse_query($wp_query)
{
    global $marvlOpts, $wp_query;
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
    if( !empty($wp_query->query_vars['marvl_project_state']) )
    {
        $marvlOpts->project_state = intval($wp_query->query_vars['marvl_project_state']);
    }
    if( !empty($wp_query->query_vars['marvl_software']) )
    {
        $marvlOpts->software_id = intval($wp_query->query_vars['marvl_software']);
    }
}

function marvl_show_project_list($project_state)
{
    global $wpdb;
    $output = "<p>In recent years group members have been awarded millions of dollars in ARC Discovery and Linkage Grants and significant investment through industry sponsored research.</p>";
    $output .= "<p>Click any individual project to learn more. View active, proposed and completed projects.</p>";

    // Add buttons to select project state.
    $output .= "<nav class=\"main-navigation\" role=\"navigation\">\n";
    $output .= "<div class=\"nav-menu\">\n<ul>\n";
    $states = array("Active","Proposed","Completed","All");
    for ($i = 1; $i <= 4; $i++) {
        $extra = "";
    if ($project_state == $i)
    {
        $extra = " current_page_item";
    }
    $state = $states[$i-1];
    $output .= "<li class=\"page_item{$extra}\"><a href=\"/current-projects/?marvl_project_state={$i}\">{$state}</a></li>\n";
    }
    $output .= "</ul></div></nav>\n";

    // Add info about each project.
    $stateEnum = array("active","proposed","completed");
    if ($project_state == 4)
    {
        $projects = $wpdb->get_results( "SELECT *
            FROM marvl_projects ORDER BY project_order, project_title");
    }
    else
    {
        $state = $stateEnum[$project_state-1];
        $projects = $wpdb->get_results( "SELECT *
            FROM marvl_projects WHERE project_state = '{$state}' ORDER BY project_order, project_title");
    }

    foreach($projects as $project)
    {
        $style = "";
        $extraclass = "";
        if (intval($project->project_image_id) > 0)
        {
            $projectUrls = $wpdb->get_results( "SELECT image_url
                        FROM marvl_images WHERE image_id = 
                        {$project->project_image_id}");
            $style = " style=\"background-image: url({$projectUrls[0]->image_url});\"";
            $extraclass = " with_image";
        }
        $output .= "<a class=\"marvl_object_link\" href=\"?marvl_project={$project->project_id}\">";
        $output .= "<div class=\"object\"><h2>{$project->project_title}</h2>\n";
        $output .= "<div class=\"marvl_software{$extraclass}\" {$style}>\n";
        $output .= "{$project->project_summary_html}\n";
        if (strlen($project->project_funding) > 0)
        {
            $output .= "<p><b>Funding:</b> {$project->project_funding}</p>\n";
        }
        $output .= "</div></div></a>\n";
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
        $output .= "<div class=\"marvl_software object\">\n";
        $output .= "<h2>{$project->project_title}</h2>\n";
        $output .= "{$project->project_description_html}\n";
        if (strlen($project->project_funding) > 0)
        {
            $output .= "<p><b>Funding:</b> {$project->project_funding}</p>\n";
        }
        $output .= "</div>\n";
        if (strlen($project->project_url) > 0)
        {
            $output .= "<p>Website: <a href=\"{$project->project_url}\">{$project->project_url}</a></p>\n";
        }
        if (intval($project->project_image_id) > 0)
        {
            $projectUrls = $wpdb->get_results( "SELECT image_url, image_caption
                        FROM marvl_images WHERE image_id = 
                        {$project->project_image_id}");
            $output .= "<p><a href=\"{$projectUrls[0]->image_url}\"><img style=\"border: 0px; width: 624px;\" src=\"{$projectUrls[0]->image_url}\" /></a><br />{$projectUrls[0]->image_caption}</p>";
        }
    }

    return $output;
}


function marvl_show_software_list()
{
    global $wpdb;
    $output = "<p>Click any piece of software we produce to learn more about it.</p>";
    
    $softwares = $wpdb->get_results( "SELECT *
            FROM marvl_software ORDER BY software_order");

    foreach($softwares as $software)
    {
        $style = "";
        $extraclass= "";
        if (intval($software->software_image_id) > 0)
        {
            $softwareUrls = $wpdb->get_results( "SELECT image_url
                        FROM marvl_images WHERE image_id = 
                        {$software->software_image_id}");
            $style = " style=\"background-image: url({$softwareUrls[0]->image_url});\"";
            $extraclass = " with_image";
        }
        $output .= "<a class=\"marvl_object_link\" href=\"?marvl_software={$software->software_id}\"><div class=\"marvl_software object{$extraclass}\" {$style}>\n";
        $output .= "<h2>{$software->software_title}</h2>\n";
        $output .= "{$software->software_description_intro_html}\n";
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
        $output .= "{$software->software_description_intro_html}\n";
        $output .= "{$software->software_description_more_html}\n";
        $output .= "</div>\n";
        if (strlen($software->software_url) > 0)
        {
            $output .= "<p>Website: <a href=\"{$software->software_url}\">{$software->software_url}</a></p>\n";
        }
        if (intval($software->software_image_id) > 0)
        {
            $softwareUrls = $wpdb->get_results( "SELECT image_url, image_caption
                        FROM marvl_images WHERE image_id = 
                        {$software->software_image_id}");
            $output .= "<p><a href=\"{$softwareUrls[0]->image_url}\"><img style=\"border: 0px; width: 624px;\" src=\"{$softwareUrls[0]->image_url}\" /></a><br />{$softwareUrls[0]->image_caption}</p>";
        }
    }

    return $output;
}

function marvl_show_publications_list()
{
    global $wpdb;
    $output = "<p>The following is a list of publications authored by IALab members.</p>";

    $publication_years = $wpdb->get_results( "SELECT DISTINCT publication_date
            FROM marvl_publications ORDER BY publication_date DESC;");
    foreach($publication_years as $publication_years_row)
    {
        $publication_year = $publication_years_row->publication_date;
        $output .= "<h2>{$publication_year}</h2>\n";


        $publications = $wpdb->get_results( "SELECT *
                FROM marvl_publications WHERE publication_date = 
                '{$publication_year}' ORDER BY publication_date DESC;");

        $output .= "<ul class=\"publications\">\n";
        foreach($publications as $publication)
        {
            $output .= "<li>{$publication->publication_authors}.<br />";
            $output .= "{$publication->publication_title}.<br />";

            $contentBefore = false;
            if ((strlen($publication->publication_conference) + strlen($publication->publication_journal)) > 0)
            {
                $contentBefore = true;
                $output .= "In <i>{$publication->publication_conference}{$publication->publication_journal}</i>";
                if (strlen($publication->publication_volume) > 0)
                {
                    $output .= " <b>{$publication->publication_volume}</b>";
                    if (strlen($publication->publication_issue) > 0)
                    {
                        $output .= "({$publication->publication_issue})";
                    }
                }
                elseif (strlen($publication->publication_issue) > 0)
                {
                    $output .= ", issue {$publication->publication_issue}";
                }
            }
            if (strlen($publication->publication_pages) > 0)
            {
                if ($contentBefore)
                {
                    $output .= ", ";
                }
                $contentBefore = true;
                $output .= "pages {$publication->publication_pages}";
            }
            if (strlen($publication->publication_publisher) > 0)
            {
                if ($contentBefore)
                {
                    $output .= ", ";
                }
                $contentBefore = true;
                $output .= "{$publication->publication_publisher}";
            }
            if ($contentBefore)
            {
                $output .= ", ";
            }
            $contentBefore = true;
            $output .= "{$publication->publication_date}.";
            if (strlen($publication->publication_pdf) > 0)
            {
                $output .= " <a href=\"{$publication->publication_pdf}\">[PDF]</a>";
            }
            $output .= "</li>\n";
        }
        $output .= "</ul>\n";
    }

    return $output;
}

function marvl_output_members($members)
{
    $output = "";
    foreach($members as $member)
    {
        $output .= "<a class=\"marvl_object_link\" href=\"?marvl_member={$member->member_id}\"><div class=\"marvl_member object\" style=\"background-image: url({$member->member_image_url});\">\n";
        $output .= "<h2>{$member->member_name}</h2>\n";
        $output .= "<h3>{$member->member_title}</h3>\n";
        $output .= "{$member->member_interests_html}\n";
        $output .= "</div></a>\n";
    }
    return $output;
}

function marvl_show_member_list()
{
    global $wpdb;
    $output = "<p>Click any individual lab member to learn more about them.</p>";

    $members = $wpdb->get_results( "SELECT *
            FROM marvl_members WHERE member_status = 1 ORDER BY member_position");
    $output .= marvl_output_members($members);


    $output .= "<p>&nbsp;</p><h2>Collaborators:</h2>";
    $members = $wpdb->get_results( "SELECT *
            FROM marvl_members WHERE member_status = 3 ORDER BY member_position");
    $output .= marvl_output_members($members);

    $output .= "<p>&nbsp;</p><h2>Past lab members:</h2>";
    $members = $wpdb->get_results( "SELECT *
            FROM marvl_members WHERE member_status = 2 ORDER BY member_position");
    $output .= marvl_output_members($members);

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
        $output .= "<h2>{$member->member_title}</h2>\n";
        $output .= "<h3>{$member->member_affiliation}</h3>\n";
        $output .= "{$member->member_interests_html}\n";
        $output .= "</div>\n";
        $output .= "{$member->member_bio_html}\n";
        if (strlen($member->member_web_url) > 0)
        {
            $output .= "<p>Website: <a href=\"{$member->member_web_url}\">{$member->member_web_url}</a></p>\n";
        }
        if (strlen($member->member_scholar_profile_url) > 0)
        {
            $output .= "<p>Google Scholar profile: <a href=\"{$member->member_scholar_profile_url}\">{$member->member_scholar_profile_url}</a></p>\n";
        }
        if (strlen($member->member_researchgate_profile_url) > 0)
        {
            $output .= "<p>ResearchGate profile: <a href=\"{$member->member_researchgate_profile_url}\">{$member->member_researchgate_profile_url}</a></p>\n";
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
        elseif ($instruction == "marvl-publications")
        {
            $output = marvl_show_publications_list(); 
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
        $output = marvl_show_project_list($marvlOpts->project_state);
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
            FROM marvl_members ORDER BY member_position");

    foreach($members as $member)
    {
        $name = preg_replace("/Prof\. /", "", $member->member_name, 1);
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
        $items = preg_replace("/(<a href=\"http:\/\/marvl.infotech.monash.edu.au\/current-projects\/\">Research<\/a>)/", "\\1{$submenu}", $items);

    }

    return $items;
}


// We want to change just the page title at the top of the page.
function marvl_the_title($title, $id)
{
    global $wpdb, $marvlOpts;

    if( ($title == "People" || $title == "Members") && in_the_loop() )
    {
        // Work around the fact the_title is called for the titles of all 
        // pages in the menus, etc.  in_the_loop causes it not to change the
        // "Members" page title in the menus.
        $member_id = $marvlOpts->member_id;
        if ($member_id > 0)
        {
            $members = $wpdb->get_results( "SELECT *
                    FROM marvl_members WHERE member_id = {$member_id}");

            if (count($members) == 1)
            {
                $title = $members[0]->member_name;
            }
        }
    }
    return $title;   
}



add_filter('the_title', 'marvl_the_title', 10, 2); 
//add_filter('wp_list_pages', 'marvl_pages_items');
add_filter('query_vars', 'marvl_filter_query_vars');
add_filter('parse_query', 'marvl_filter_parse_query');
add_filter('the_content', 'marvl_the_content'); 

add_action('activate_plugindir/pluginfile.php', 'marvl_install');




/* vim: ts=4 sw=4 et tw=0 wm=0
*/
?>
