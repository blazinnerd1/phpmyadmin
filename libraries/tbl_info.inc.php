<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * extracts table properties from create statement
 *
 * @todo should be handled by class Table
 * @todo this should be recoded as functions, to avoid messing with global variables
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/Table.class.php';

/**
 * requirements
 */
require_once './libraries/common.inc.php';

// Check parameters
PMA_checkParameters(array('db', 'table'));

/**
 * Defining global variables, in case this script is included by a function.
 * This is necessary because this script can be included by libraries/header.inc.php.
 */
global $showtable, $tbl_is_view, $tbl_type, $show_comment, $tbl_collation,
       $table_info_num_rows, $auto_increment;

/**
 * Gets table informations
 */
// Seems we need to do this in MySQL 5.0.2,
// otherwise error #1046, no database selected
PMA_DBI_select_db($GLOBALS['db']);

// PMA_Table::sGetStatusInfo() does caching by default, but here
// we force reading of the current table status
// if $reread_info is true (for example, coming from tbl_operations.php
// and we just changed the table's storage engine)
$showtable = PMA_Table::sGetStatusInfo($GLOBALS['db'], $GLOBALS['table'], null, (isset($reread_info) && $reread_info ? true : false));

// need this test because when we are creating a table, we get 0 rows
// from the SHOW TABLE query
// and we don't want to mess up the $tbl_type coming from the form

if ($showtable) {
    if (PMA_Table::isView($GLOBALS['db'], $GLOBALS['table'])) {
        $tbl_is_view     = true;
        $tbl_type        = $GLOBALS['strView'];
        $show_comment    = null;
    } else {
        $tbl_is_view     = false;
        $tbl_type        = isset($showtable['Engine'])
            ? strtoupper($showtable['Engine'])
            : '';
        // a new comment could be coming from tbl_operations.php
        // and we want to show it in the header
        if (isset($submitcomment) && isset($comment)) {
            $show_comment = $comment;
        } else {
            $show_comment    = isset($showtable['Comment'])
                ? $showtable['Comment']
                : '';
        }
    }
    $tbl_collation       = empty($showtable['Collation'])
        ? ''
        : $showtable['Collation'];

    if (null === $showtable['Rows']) {
        $showtable['Rows']   = PMA_Table::countRecords($GLOBALS['db'],
            $showtable['Name'], true, true);
    }
    $table_info_num_rows = isset($showtable['Rows']) ? $showtable['Rows'] : 0;
    $row_format = isset($showtable['Row_format']) ? $showtable['Row_format'] : '';
    $auto_increment      = isset($showtable['Auto_increment'])
        ? $showtable['Auto_increment']
        : '';

    $create_options      = isset($showtable['Create_options'])
        ? explode(' ', $showtable['Create_options'])
        : array();

    // export create options by its name as variables into global namespace
    // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
    unset($pack_keys);
    foreach ($create_options as $each_create_option) {
        $each_create_option = explode('=', $each_create_option);
        if (isset($each_create_option[1])) {
            $$each_create_option[0]    = $each_create_option[1];
        }
    }
    // we need explicit DEFAULT value here (different from '0')
    $pack_keys = (!isset($pack_keys) || strlen($pack_keys) == 0) ? 'DEFAULT' : $pack_keys;
    unset($create_options, $each_create_option);
} // end if
?>
