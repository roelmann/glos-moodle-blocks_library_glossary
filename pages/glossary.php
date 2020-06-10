<?php
// This file is part of The Bootstrap 3 Moodle theme
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Course block page.
 *
 * @package    page_coursepage
 * @author     2019 Richard Oelmann
 * @copyright  2019 R. Oelmann

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Ref: http://docs.moodle.org/dev/Page_API.
ini_set ('display_errors', 'on');
ini_set ('log_errors', 'on');
ini_set ('display_startup_errors', 'on');
ini_set ('error_reporting', E_ALL);

require_once('../../../config.php');
global $COURSE, $DB, $PAGE, $USER, $CFG;

// Sets url and the page settings.
$PAGE->set_context(context_system::instance());
$thispageurl = new moodle_url('/blocks/library_glossary/pages/glossary.php');
$PAGE->set_url($thispageurl, $thispageurl->params());
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Glossary Page');
$PAGE->set_heading('Glossary Page');

// No edit.
$USER->editing = $edit = 0;
$PAGE->navbar->ignore_active();
$PAGE->navbar->add($PAGE->title, $thispageurl);
$option = isset($_POST['subjectselect']) ? $_POST['subjectselect'] : false;

// Output.
echo $OUTPUT->header();
echo $OUTPUT->box_start();

$glossaryname = 'Library subject resource glossary';
$course = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname LIKE 'LibRes'");
// Gets all the glossary data ready to be split into arrays for the different sections of the page.
$sql = "SELECT ge.* FROM {glossary_entries} ge JOIN {glossary} g ON g.id = ge.glossaryid WHERE g.name = '" . $glossaryname . "' order by ge.concept";
$data = new stdClass();
$data = $DB->get_records_sql($sql);

// Initialises the arrays ready for use.
$guidearray = array();
$photoarray = array();
$otherarray = array();
$azlistarray = array();
$newresarray = array();
$keyresourcesarray = array();
$supplementaryarray = array();
$subjectlibrarianarray = array();
$blogarray = array();
$glossentrylastid = "";
$subjectcommunity = "";

$gm = $DB->get_record('modules', array('name' => 'glossary'));
$glos = $DB->get_record('glossary', array('name' => $glossaryname, 'course' => $course->id));
$cm = $DB->get_record('course_modules', array('module' => $gm->id, 'instance' => $glos->id));
$glossaryctx = context_module::instance($cm->id);

// Checks that the subject is being passed through from the block.
if ($option) {
    $subjectcommunity = $option;
    $subjectarray = explode('-', $subjectcommunity);
    $subjectarray = explode('|', $subjectarray[1]);
    $subjectcommunity = strtolower($subjectarray[0]);
}

$blogtag = 'lib#'.$subjectcommunity.'_blog';
$blogsql = "SELECT p.*, ti.contextid as ctxid FROM {post} p
                JOIN {tag_instance} ti ON ti.itemid = p.id
                JOIN {tag} t ON t.id = ti.tagid
            WHERE t.name = '".$blogtag."'
                AND ti.itemtype = 'post'
                AND p.module = 'blog'
                AND p.publishstate = 'site'
            ORDER BY p.lastmodified DESC
            LIMIT 3;";
$blogquery = $DB->get_records_sql($blogsql);

foreach ($blogquery as $blog) {
    $blogarray[] = $blog;
}

// Loops through the glossary data.
foreach ($data as $glossary) {
    $glossary->definition = file_rewrite_pluginfile_urls($glossary->definition, 'pluginfile.php', $glossaryctx->id, 'mod_glossary', 'entry', $glossary->id);
    $options = new stdClass();
    $options->trusted = $glossary->definitiontrust;
    $options->overflowdiv = true;

    // Gets the tags for the data.
    $sql2 = "SELECT t.*, ti.* FROM {tag} t JOIN {tag_instance} ti ON ti.tagid = t.id WHERE ti.itemid = " . $glossary->id;
    $tags = $DB->get_records_sql($sql2);
    // Loops through the tags storing them in an array.
    foreach ($tags as $tag) {
        $glossary->tags[] = $tag->name;
    }

    // Checks to make sure the tags are not empty.
    if (!(empty($glossary->tags))) {
        // Loops through the tags
        foreach ($glossary->tags as $tag) {
            // Checks that the tag is a library tag.
            if (substr($tag, 0, 4) == 'lib#') {
                // Splits the library tag and then splits the second element of the library tag.
                $communityarray = explode('#', $tag);
                $communityarray2 = explode('_', $communityarray[1]);
                // Stores the first array element after the second split.
                $subcomms = $communityarray2[0];

                // Checks to see if the library tag is an azres tag if so stores this in the azlist array.
                if ($subcomms == 'azres') {
                    $azlistarray[] = $glossary;
                }
                // Checks to see if tag equals option passed from block and is not azlist.
                if (($subcomms == $subjectcommunity || $subcomms == 'all') && $subcomms != "azres") {
                    // Splits the tag on the _ so that we can tell what type of tag we have.
                    $suffixarray = explode('_', $tag);
                    // Checks to make sure that count is greater than 1 and checks glossary ids do not match.
                    if (count($suffixarray) > 0 && $glossentrylastid != $glossary->id) {
                        // Switch statement checking what value is stored in array and storing into array required
                        // for page dependent on value passed.
                        switch($suffixarray[1]) {
                            case 'photo':
                                $photoarray[] = $glossary;
                                break;
                            case 'guide':
                                $guidearray[] = $glossary;
                                break;
                            case 'sd':
                                $supplementaryarray[] = $glossary;
                                break;
                            case 'kd':
                                $keyresourcesarray[] = $glossary;
                                break;
                            case 'new':
                                $newresarray[] = $glossary;
                                break;
                            case 'sl':
                                $subjectlibrarianarray[] = $glossary;
                                break;
//                            case 'blog':
//                                $blogarray = $glossary;
//                                break;

                            default:
                                $otherarray[] = $glossary;
                                break;
                        }
                    }
                }

            }
        }
    }
    $glossentrylastid = $glossary->id;

}

$subjects = get_subjects_page();
$pagelink = new moodle_url ('/blocks/library_glossary/pages/glossary.php');
$sbjpost = explode('|',$_POST['subjectselect']);
foreach ($subjects as $subject) {
    $sbj = explode('|',$subject['category_idnumber']);
    if($sbj[0] == $sbjpost[0]) {
        $lgtitle = '<h2>'.$subject["category_name"].'<span style="font-size:60%;">  ('.$sbj[0].')</span></h2>';
    }
}
?>
<div>
    <!-- Html output for layout of glossary page.   -->
   <div class="row">
       <div class="col">
<!--           <h2> -->
               <?php // Get language string for library sub heading.
//               echo get_string('librarysubhead', 'block_library_glossary');
//               echo $option. ' ' .$_POST['subjectselect'].'<br>';
                echo $lgtitle;
               ?>
<!--           </h2> -->
       </div>
   </div>
   <div class="row">
        <div class="col-2 libreturn">
        <?php $url = new moodle_url('/course/view.php', array('id' => $course->id)); ?>
            <a href="<?php echo $url; ?>" alt="Return link">
                <i class = "fa fa-mail-reply">&nbsp;</i>
                <p>Return to Library Resources Page</p>
            </a>
        </div>
        <div class="col-2">
            &nbsp;
        </div>
        <div class="col-8">
            <h5 class = "subjectsearch mx-auto text-center">
                Search for alternative subject area
            </h5>
            <form class="d-flex" method="post" action="<?php echo $pagelink; ?>">
                <select class='subject-select' name = 'subjectselect'>
                    <option value="">Select Subject Area...</option>
                <?php
                foreach ($subjects as $subject) {
                ?>
                    <option value= "<?php echo $subject['category_idnumber'];?>">
                        <?php
                        echo /*$subject['category_idnumber'] . '-' .*/ $subject['category_name'];
                        ?>
                    </option>
                <?php
                }
                ?>
                </select>
                <input type='submit' value='Submit'/>
            </form>
        </div>
    </div>

    <!-- Search box section -->
    <div class="row glossary-search">
        <div class="col-12 pt-3 pb-3">
            <div id="discovery-search-box" style="margin: 1em 0px 2em;"><link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous"><style type="text/css">#discovery-search-box,#discovery-search-box *{box-sizing:border-box!important;margin:0;padding:0;text-align:left}#discovery-search-box .material-tab{display:inline-block;user-select:none;cursor:pointer;background-color:#545454;color:#fff;min-height:2em;min-width:1.5rem;padding:.5em;box-sizing:border-box;border-radius:.25em .25em 0 0;margin-right:.4em;z-index:2}#discovery-search-form{position:'relative';width:auto;padding:1.25em 2em 2em;background-color:#f2f2f2!important;color:#333!important;z-index:1;line-height:initial}#discovery-search-box .material-tab.active-tab{background-color:#f2f2f2!important;color:#333!important}#discovery-index-container{position:relative;height:3em;background-color:white;color:black}#discovery-index-container:after{content:'\f078';display:block;font-family:'FontAwesome',sans-serif;position:absolute;top:0;right:.5em;line-height:3em;z-index:5}#discovery-index-container #discovery-search-select{position:relative;z-index:10;height:100%;min-width:initial;font-size:1em;padding:0 2em 0 1em;background-color:transparent;border-top:1px solid #ccc;border-right:0;border-bottom:1px solid #ccc;border-left:1px solid #ccc;border-image:initial;border-radius:0;-webkit-appearance:none;-moz-appearance:none}select::-ms-expand{display:none}</style>
                <div style="display: flex; font-size: 1.1em; box-sizing: border-box; text-align: center;"></div>
                <form id="discovery-search-form">
                    <label for="discovery-search" style="display: inline-block; font-size: 1.3em; font-weight: normal; margin-bottom: 0.5em;">Search Library Discovery</label>
                    <div style="display: flex; width: 100%;">
                        <div style="display: flex; flex-grow: 1;">
                            <input type="text" id="discovery-search" style="display: inline-block; width: 100%; height: 3em; font-size: 1em; padding: 0px 0.5em; margin-bottom: 0.5em; color: black; background-color: white; border: 1px solid rgb(204, 204, 204); box-shadow: none;" required="" autocomplete="off">
                        </div>
                        <div>
                            <input type="submit" value="Search" style="padding: 0.75em 1.5em; font-size: 1em; width: auto; height: 3em; min-width: 8em; color: rgb(255, 255, 255); background-color: rgb(34, 120, 181); margin: 0px 0px 0px 0.5em; border-radius: 0.25em; border: medium none; background-image: none; float: none; text-align: center;">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                    </div>
                </form>
                <script type="text/javascript">
                    (function(){var d,w,tabList,h,form,input,urlBase,active,facets,v,r,rt,a,f,select,query;d=document;w=window;r=(function(){try{return w.blank!==w.top;}catch(e){return true;}})();rt=r?'_blank':'_self';a=d.getElementById('discovery-advanced-search');if(a)a.setAttribute('target',rt);tabList=d.querySelectorAll('#discovery-search-box span.material-tab');tabList=[].slice.call(tabList);h=function(e){if(e.keyCode&&e.keyCode!==13)return;tabList.forEach(function(it){it.className='material-tab';});this.className='material-tab active-tab';};tabList.forEach(function(tab){tab.addEventListener('click',h);tab.addEventListener('keydown',h);});form=d.getElementById('discovery-search-form');input=d.getElementById('discovery-search');select=d.getElementById('discovery-search-select');urlBase='https://glos.on.worldcat.org/external-search?queryString=#T#&clusterResults=on&stickyFacetsChecked=on#F#';form.addEventListener('submit',function(e){e.preventDefault();e.stopPropagation();f='';active=d.querySelector('.material-tab.active-tab');if(active){facets=JSON.parse(active.getAttribute('data-facets')||'[]');facets.forEach(function(facet){console.log(facet);if(facet.key&&facet.value&&facet.value!=='all'){f+='&'+facet.key+'='+facet.value;}})}query=input.value;if(select){var index=select.options[select.selectedIndex].value
                        if(index!=='kw')query=select.options[select.selectedIndex].value+':'+query;}w.open(urlBase.replace('#T#',encodeURIComponent(query)).replace('#F#',f),rt);});})()</script>
            </div>
        </div>
    </div>
   <!-- Nav tabs using bootstrap 4. -->
    <ul class="nav nav-tabs glossary-nav-tabs row" id="myTab" role="tablist">
        <!-- Key resources tab. -->
        <li class="nav-item glossary-nav-item active">
            <a class="nav-link active glossary-nav-link"
               id="keyresources-tab"
               data-toggle="tab"
               href="#keyresources"
               role="tab"
               aria-controls="keyresources"
               aria-selected="true">
                <?php echo get_string('keyresourceshead', 'block_library_glossary') ?>
            </a>
        </li>
        <!-- Supplementary tab. -->
        <li class="nav-item glossary-nav-item">
            <a class="nav-link glossary-nav-link"
               id="supplementary-tab"
               data-toggle="tab"
               href="#supplementary"
               role="tab"
               aria-controls="supplementary"
               aria-selected="false">
                <?php echo get_string('supplementaryhead', 'block_library_glossary') ?>
            </a>
        </li>
        <!-- AZlist tab. -->
        <li class="nav-item glossary-nav-item">
            <a class="nav-link glossary-nav-link"
               id="azlist-tab"
               data-toggle="tab"
               href="#azlist"
               role="tab"
               aria-controls="azlist"
               aria-selected="false">
                <?php echo get_string('azlisthead', 'block_library_glossary') ?>
            </a>
        </li>
        <!-- Guide tab. -->
        <li class="nav-item glossary-nav-item">
            <a class="nav-link glossary-nav-link"
               id="guide-tab"
               data-toggle="tab"
               href="#guide"
               role="tab"
               aria-controls="guide"
               aria-selected="false">
                <?php echo get_string('guideshead', 'block_library_glossary') ?>
            </a>
        </li>
        <!-- New Resource tab. -->
        <li class="nav-item glossary-nav-item">
            <a class="nav-link"
               id="newres-tab"
               data-toggle="tab"
               href="#newres"
               role="tab"
               aria-controls="newres"
               aria-selected="false">
                <?php echo get_string('newreshead', 'block_library_glossary') ?>
            </a>
        </li>
    </ul>
    <!-- Content area for tabs. -->
    <div class="row">
        <div class="tab-content col-6" id="myTabContent">
            <!-- Key resources content area. -->
            <div class="tab-pane fade show active" id="keyresources" role="tabpanel" aria-labelledby="keyresources-tab">
                <!-- Sets count variable used for all expandable content. -->
                <?php $x = 1;
                ?>
                <?php
                // Checks to make sure Key Resources array is not empty.
                if (!(empty($keyresourcesarray))) {
                    // Loops through key resources.
                    foreach ($keyresourcesarray as $keyresource) { ?>
                        <!-- html structure set for collapsable elements for each entry -->
                        <div class="card">
                            <div class="card-header" id="heading<?php echo $x;?>">
                                <h5 class="mb-0">
                                    <button class="btn btn-link glossary-btn-link"
                                            type="button"
                                            data-toggle="collapse"
                                            data-target="#collapse<?php echo $x;?>"
                                            aria-expanded="false" aria-controls="collapse<?php echo $x;?>">
                                        <?php // Output heading here.
                                        echo $keyresource->concept; ?>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse<?php echo $x;?>"
                                 class="collapse"
                                 aria-labelledby="heading<?php echo $x;?>"
                                 data-parent="#keyresources">
                                <div class="card-body">
                                    <?php // Output content here.
                                    echo $keyresource->definition;  ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        // Increment counter otherwise expandable functionality will not work.
                        $x++; ?>
                    <?php }
                } ?>
            </div>
            <!-- Supplementary content area. -->
            <div class="tab-pane fade" id="supplementary" role="tabpanel" aria-labelledby="sypplementary-tab">
                <?php
                // Checks to make sure Supplementary array is not empty.
                if (!(empty($supplementaryarray))) {
                    // Loops through Supplementary Resources.
                    foreach ($supplementaryarray as $supplementaryresource) { ?>
                        <!-- html structure set for collapsable elements for each entry -->
                        <div class="card">
                            <div class="card-header" id="heading<?php echo $x; ?>">
                                <h5 class="mb-0">
                                    <button class="btn btn-link glossary-btn-link" type="button" data-toggle="collapse"
                                            data-target="#collapse<?php echo $x; ?>" aria-expanded="false"
                                            aria-controls="collapse<?php echo $x; ?>">
                                        <?php // Output heading here.
                                        echo $supplementaryresource->concept; ?>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse<?php echo $x; ?>"
                                 class="collapse"
                                 aria-labelledby="heading<?php echo $x; ?>"
                                 data-parent="#supplementary">
                                <div class="card-body">
                                    <?php // Output content here.
                                    echo $supplementaryresource->definition; ?>
                                </div>
                            </div>
                        </div>
                        <?php  // Increment counter otherwise expandable functionality will not work.
                        $x++; ?>
                    <?php }
                } ?>
            </div>
            <!-- AZ list content area. -->
            <div class="tab-pane fade" id="azlist" role="tabpanel" aria-labelledby="azlist-tab">
                <?php
                // Checks to make sure azlist array is not empty.
                if (!(empty($azlistarray))) {
                    // Loops through A-Z Resources.
                    foreach ($azlistarray as $azlistresource) { ?>
                        <!-- html structure set for collapsable elements for each entry -->
                        <div class="card">
                            <div class="card-header" id="heading<?php echo $x; ?>">
                                <h5 class="mb-0">
                                    <button class="btn btn-link glossary-btn-link" type="button" data-toggle="collapse"
                                            data-target="#collapse<?php echo $x; ?>" aria-expanded="false"
                                            aria-controls="collapse<?php echo $x; ?>">
                                        <?php // Output heading here.
                                        echo $azlistresource->concept; ?>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse<?php echo $x; ?>"
                                 class="collapse"
                                 aria-labelledby="heading<?php echo $x; ?>"
                                 data-parent="#azlist">
                                <div class="card-body">
                                    <?php // Output content here.
                                    echo $azlistresource->definition; ?>
                                </div>
                            </div>
                        </div>
                        <?php  // Increment counter otherwise expandable functionality will not work.
                        $x++; ?>
                    <?php }
                } ?>
            </div>
            <!-- Guide content area. -->
            <div class="tab-pane fade" id="guide" role="tabpanel" aria-labelledby="guide-tab">
                <?php
                // Checks to make sure guide array is not empty.
                if (!(empty($guidearray))) {
                    // Loops through guide resources.
                    foreach ($guidearray as $guideresource) { ?>
                        <!-- html structure set for collapsable elements for each entry -->
                        <div class="card">
                            <div class="card-header" id="heading<?php echo $x; ?>">
                                <h5 class="mb-0">
                                    <button class="btn btn-link glossary-btn-link" type="button" data-toggle="collapse"
                                            data-target="#collapse<?php echo $x; ?>" aria-expanded="false"
                                            aria-controls="collapse<?php echo $x; ?>">
                                        <?php // Output heading here.
                                        echo $guideresource->concept; ?>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse<?php echo $x; ?>"
                                 class="collapse"
                                 aria-labelledby="heading<?php echo $x; ?>"
                                 data-parent="#guide">
                                <div class="card-body">
                                    <?php // Output content here.
                                    echo $guideresource->definition; ?>
                                </div>
                            </div>
                        </div>
                        <?php  // Increment counter otherwise expandable functionality will not work.
                        $x++; ?>
                    <?php }
                } ?>
            </div>
            <!-- New resources content area. -->
            <div class="tab-pane fade" id="newres" role="tabpanel" aria-labelledby="newres-tab">
                <?php
                // Checks to make sure new resources array is not empty.
                if (!(empty($newresarray))) {
                    // Loops through new Resources.
                    foreach ($newresarray as $newresource) { ?>
                        <!-- html structure set for collapsable elements for each entry -->
                        <div class="card">
                            <div class="card-header" id="heading<?php echo $x; ?>">
                                <h5 class="mb-0">
                                    <button class="btn btn-link glossary-btn-link" type="button" data-toggle="collapse"
                                            data-target="#collapse<?php echo $x; ?>" aria-expanded="false"
                                            aria-controls="collapse<?php echo $x; ?>">
                                        <?php echo $newresource->concept; ?>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapse<?php echo $x; ?>"
                                 class="collapse"
                                 aria-labelledby="heading<?php echo $x; ?>"
                                 data-parent="#guide">
                                <div class="card-body">
                                    <?php // Output content here.
                                    echo $newresource->definition; ?>
                                </div>
                            </div>
                        </div>
                        <?php $x++; ?>
                    <?php }
                } ?>
            </div>
        </div>
        <div class="col-6 right-content-area">
            <div class="row">
                <div class="col">
                    <div class="row">
                        <div class="col-6 librarianphoto">
                            <?php

                            if (!(empty($photoarray))) {
                                foreach ($photoarray as $photo) {
                                    echo format_text($photo->definition, $photo->definitionformat, $options);
                                }
                            } ?>
                        </div>

                        <div class="col-6">
                            <?php
                            if (!(empty($subjectlibrarianarray))) {
                                foreach ($subjectlibrarianarray as $subjectlibrarian) {
                                    echo "<h4>" . $subjectlibrarian->concept . "</h4>";
                                    echo format_text($subjectlibrarian->definition, $subjectlibrarian->definitionformat, $options);
                                    ?>
<!--
                                    <a href="http://www.twitter.com"><i class="fa fa-twitter-square"></i></a>
                                    <a href="http://www.facebook.com"><i class="fa fa-facebook-square"></i></a>
-->
                                    <?php
                                }
                            }?>
                        </div>
                    </div>
                    <div class="glossary-left-padding mt-2">
                        <h4 class="librarianheading">
                            <?php
                            echo get_string('librariansubjectstext', 'block_library_glossary');
                            ?>
                        </h4>
                        <p>
                            <?php
                            $i = 1;
                            if (!(empty($subjectlibrarian->tags))) {
                                $subjs = count($subjectlibrarian->tags);
                                foreach ($subjectlibrarian->tags as $librariantag) {
                                    $libtagarray = explode('#', $librariantag);
                                    $libtagarray2 = explode('_', $libtagarray[1]);
                                    $libsub = $libtagarray2[0];
                                    $sql3 = "SELECT category_idnumber, category_name FROM
                                    integrations.usr_data_categories WHERE category_idnumber LIKE '%$libsub%'";
                                    $subject = $DB->get_records_sql($sql3);
                                    $subjectid = key($subject);
                                    if (!(empty($subject[$subjectid]->category_name))) {
                                        echo $subject[$subjectid]->category_name;
                                        if ($i < $subjs) {
                                            echo " | ";
                                        }
                                        $i++;
                                    }
                                }
                            }
                            ?>
                        </p>
                    </div>

                    <div class="row blog border-top glossary-left-padding">
                        <h4>
                            <?php echo get_string('newsitems', 'block_library_glossary') ?>
                        </h4>
                        <?php
                        if (!(empty($blogarray))) {
                            foreach ($blogarray as $blogentry) {
                                $context = context_system::instance();
                                $blogentry->summary = file_rewrite_pluginfile_urls($blogentry->summary, 'pluginfile.php', $context->id, 'blog', 'post', $blogentry->id);
                                ?>
                                <div class="row px-2">
                                    <?php
                                    $blogurl = new moodle_url('/blog/index.php',array('entryid'=>$blogentry->id));
                                    echo "<a class='blogtitle' href='".$blogurl."'> <h5>" . $blogentry->subject . "</h5></a>";
                                    echo "<span class='blogbody'>" . format_text($blogentry->summary, $blogentry->summaryformat, $options) . "</span>";
                                    ?>
                                </div>
                            <?php }
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

function get_subjects_page() {
        global $COURSE, $DB, $PAGE, $USER, $CFG;
        $externaldb = new \local_extdb\extdb();
        $name = $externaldb->get_name();

        $externaldbtype = $externaldb->get_config('dbtype');
        $externaldbhost = $externaldb->get_config('dbhost');
        $externaldbname = $externaldb->get_config('dbname');
        $externaldbencoding = $externaldb->get_config('dbencoding');
        $externaldbsetupsql = $externaldb->get_config('dbsetupsql');
        $externaldbsybasequoting = $externaldb->get_config('dbsybasequoting');
        $externaldbdebugdb = $externaldb->get_config('dbdebugdb');
        $externaldbuser = $externaldb->get_config('dbuser');
        $externaldbpassword = $externaldb->get_config('dbpass');

        // Tables relating to the reassessment groups stored in language file and called here.
        $categorytable = get_string('category_table', 'block_library_glossary');

        // Database connection and setup checks.
        // Check connection and label Db/Table in cron output for debugging if required.
        if (!$externaldbtype) {
            echo 'Database not defined.<br>';
            return 0;
        }
        // Check remote assessments table - usr_data_assessments.
        if (!$categorytable) {
            echo 'Levels Table not defined.<br>';
            return 0;
        }
        // Check remote student grades table - usr_data_student_assessments.
        if (!$categorytable) {
            echo 'Categories Table not defined.<br>';
            return 0;
        }

        // DB check.
        // Report connection error if occurs.
        if (!$extdb = $externaldb->db_init(
            $externaldbtype,
            $externaldbhost,
            $externaldbuser,
            $externaldbpassword,
            $externaldbname)) {
            echo 'Error while communicating with external database <br>';
            return 1;
        }
        $sql = "SELECT category_idnumber, category_name FROM " . $categorytable .
            " WHERE category_idnumber LIKE '%sub%' AND deleted = 0 AND category_name NOT LIKE '%Undefined%' AND category_name NOT LIKE '%INTOG%' ORDER BY category_name";
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($fields = $rs->FetchRow()) {
                    $fields = array_change_key_case($fields, CASE_LOWER);
                    $fields = $externaldb->db_decode($fields);
                    $categories[] = $fields;
                }
            }
            $rs->Close();
        } else {
            // Report error if required.
            $extdb->Close();
            echo 'Error reading data from the external catlevel table, ' . $categorytable . '<br>';
            return 4;

        }
        return $categories;
    }
