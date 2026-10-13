<?php
/**
 * ksf_FA_Rep_SalesGroups - Sales Group Report
 */

define('SS_KSF_FA_REP_SALESGROUPS', 147 << 9);

class hooks_ksf_FA_Rep_SalesGroups extends hooks
{
    var $module_name = 'ksf_FA_Rep_SalesGroups';
    var $version = '2.4.19-1.0.0';

    function install_access()
    {
        $security_sections[SS_KSF_FA_REP_SALESGROUPS] = _('KSF Sales Group Reports');
        $security_types['SA_KSF_REP_SALESGROUPS'] = _('View Sales Group Reports');
        return array($security_sections, $security_types);
    }

    function install_extension($install = true)
    {
        $updates = array('sql/install.sql' => array());
        if ($install) {
            $this->register_extension('ksf_FA_Rep_SalesGroups', $updates);
        }
        return $this->verify_updates($updates, !$install);
    }

    function deactivate_extension($delete = true)
    {
        return $this->remove_extension($delete);
    }

    function getModuleConstants(&$data, $opts = array())
    {
        $data['SS_KSF_FA_REP_SALESGROUPS'] = SS_KSF_FA_REP_SALESGROUPS;
    }
}
