<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


$Language->loadLanguageMsg('project/project');

//
//	get the Group object
//
$group = group_get_object($group_id);
if (!$group || !is_object($group) || $group->isError()) {
	exit_no_group();
}

if ( $atid ) {
	//	Create the ArtifactType object
	//
	$at = new ArtifactType($group,$atid);
	if (!$at || !is_object($at)) {
		exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_deps_export','at_not_created'));
	}
	if ($at->isError()) {
		exit_error($Language->getText('global','error'),$at->getErrorMessage());
	}
	// Check if this tracker is valid (not deleted)
	if ( !$at->isValid() ) {
		exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_deps_export','tracker_no_longer_valid'));
	}

        //
        //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues() 
        //
        $ath = new ArtifactTypeHtml($group,$atid);
        if (!$ath || !is_object($ath)) {
            exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_export','ath_not_created'));
        }
        if ($ath->isError()) {
            exit_error($Language->getText('global','error'),$ath->getErrorMessage());
        }

	// Create field factory
	$art_field_fact = new ArtifactFieldFactory($at);
	if ($art_field_fact->isError()) {
		exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
	}
	
	$sql = $at->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list);
}


// Add the 2 fields that we build ourselves for user convenience
// - All follow-up comments
// - Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on';

$lbl_list['follow_ups'] = $Language->getText('project_export_artifact_export','follow_up_comments');
$lbl_list['is_dependent_on'] = $Language->getText('project_export_artifact_export','depend_on');

$dsc_list['follow_ups'] = $Language->getText('project_export_artifact_export','all_followup_comments');
$dsc_list['is_dependent_on'] = $Language->getText('project_export_artifact_export','depend_on_list');

$eol = "\n";
    
//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);    

if ($export == 'artifact') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	        $tbl_name = str_replace(' ','_','artifact_'.$at->getItemName());
		header ('Content-Type: text/csv');
		header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');
	
		echo build_csv_header($col_list, $lbl_list).$eol;
		
		while ($arr = db_fetch_array($result)) {	    
		    prepare_artifact_record($at,$fields,$atid,$arr);
		    echo build_csv_record($col_list, $arr).$eol;
		}
	
    } else {

		project_admin_header(array('title'=>$pg_title));
	
		echo '<h3>'.$Language->getText('project_export_artifact_export','art_export').'</h3>';
		if ($result) {
		    echo '<P>'.$Language->getText('project_export_artifact_export','no_art_found');
		} else {
		    echo '<P>'.$Language->getText('project_export_artifact_export','db_access_err',$GLOBALS['sys_name']);
		    echo '<br>'.db_error();
		}
		site_project_footer( array() );
    }


} else if ($export == "artifact_format") {

    echo $Language->getText('project_export_artifact_export','art_exp_format');

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_artifact_record($at,$fields,$atid,$record);
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);


} else if ($export == "project_db") {


    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

		// Get the artfact type list
		$at_arr = $atf->getArtifactTypes();
		
		if ($at_arr && count($at_arr) >= 1) {
			for ($j = 0; $j < count($at_arr); $j++) {

				$tbl_name = "artifact_".$at_arr[$j]->getItemName();
				$tbl_name = str_replace(' ','_',$tbl_name);
				$atid = $at_arr[$j]->getID();
				
				//	Create the ArtifactType object
				//
				$at = new ArtifactType($group,$atid);
				if (!$at || !is_object($at)) {
					exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_deps_export','at_not_created'));
				}
				if ($at->isError()) {
					exit_error($Language->getText('global','error'),$at->getErrorMessage());
				}
				// Check if this tracker is valid (not deleted)
				if ( !$at->isValid() ) {
					break;
				}
				
                                //
                                //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues() 
                                //
                                $ath = new ArtifactTypeHtml($group,$atid);
                                if (!$ath || !is_object($ath)) {
                                    exit_error($Language->getText('global','error'),$Language->getText('project_export_artifact_export','ath_not_created'));
                                }
                                if ($ath->isError()) {
                                    exit_error($Language->getText('global','error'),$ath->getErrorMessage());
                                }


				// Create field factory
				$art_field_fact = new ArtifactFieldFactory($at);
				if ($art_field_fact->isError()) {
					exit_error($Language->getText('global','error'),$art_field_fact->getErrorMessage());
				}
				
				$col_list = array();
				$sql = $at->buildExportQuery($fields,$col_list,$lbl_list,$dsc_list);
				$col_list[] = 'follow_ups';
				$col_list[] = 'is_dependent_on';

				// Let's create the project database if it does not exist
				// Drop the existing table and create a fresh one
				db_project_create($dbname);
				db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
				
				$sql_create = "";
				reset($col_list);
				while (list(,$col) = each($col_list)) {
					$field = $art_field_fact->getFieldFromName($col);
					if ( !$field ) 
						break;
						
					if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
						$type = "TEXT";
					} else if ( $field->isTextArea() || ($field->isTextField() && $field->getDataType() == $field->DATATYPE_TEXT) ) {
						$type = "TEXT";
					} else if ( $field->isDateField() ) {
						$type = "DATETIME";
					} else if ( $field->isFloat() ) {
						$type = "FLOAT(10,2)";
					} else {
						$type = "INTEGER";
					}

				    $sql_create .= $field->getName().' '.$type.',';
								
				} // end while
				
				// Add  depend_on and follow ups
			    $sql_create .= " follow_ups TEXT, is_dependent_on TEXT";
				
				$sql_create = 'CREATE TABLE '.$tbl_name.' ('.$sql_create.')';
				$res = db_project_query($dbname, $sql_create);
			
				// extract data from the bug table and insert them into
				// the project database table
				if ($res) {
				    
					$result=db_query($sql);
				    while ($arr = db_fetch_array($result)) {
						prepare_artifact_record($at,$fields,$atid,$arr);
						insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
				    }
			
				} else {
				    $feedback .= $Language->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
				}

			} // for
		} // if 

    } else {
		$feedback .= $Language->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }

   
}

?>
