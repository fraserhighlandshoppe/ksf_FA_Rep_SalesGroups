<?php
/**
 * ksf_FA_Rep_SalesGroups - Sales Group Report
 */

define('SS_KSF_FA_REP_SALESGROUPS', 147 << 8);

class hooks_ksf_FA_Rep_SalesGroups extends hooks
{
    var $module_name = 'ksf_FA_Rep_SalesGroups';
    var $version = '2.4.19-1.0.0';

    function install_access()
    {
        $security_sections[SS_KSF_FA_REP_SALESGROUPS] = _('KSF Customer Reports');
        $security_types['SA_KSF_REP_SALESGROUPS'] = _('View Sales Group Reports');
        return array($security_sections, $security_types);
    }

    function activate_extension($company, $check_only = true)
    {
        return true;
    }

    function deactivate_extension($company, $check_only = true)
    {
        return true;
    }

    function getModuleConstants(&$data, $opts = array())
    {
        $data['SS_KSF_FA_REP_SALESGROUPS'] = SS_KSF_FA_REP_SALESGROUPS;
    }
}
