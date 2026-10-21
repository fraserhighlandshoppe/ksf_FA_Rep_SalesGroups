<?php
/**
 * ksf_FA_Rep_SalesGroups - Sales Group Report
 */

class hooks_ksf_FA_Rep_SalesGroups extends hooks
{
    var $module_name = 'ksf_FA_Rep_SalesGroups';
    var $version = '1.0.0';

    function activate_extension($company, $check_only = true)
    {
        return true;
    }

    function deactivate_extension($company, $check_only = true)
    {
        return $this->remove_extension($check_only);
    }
}
