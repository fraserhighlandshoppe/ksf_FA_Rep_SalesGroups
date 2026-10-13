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
        $security_types['SA_CUSTPAYMREP'] = _('View Customer Reports');
        return array($security_sections, $security_types);
    }

    function activate_extension($company, $check_only = true)
    {
        $updates = array(
            'sql/install.sql' => array(),
        );
        if (!$check_only) {
            $this->register_extension('ksf_FA_Rep_SalesGroups', $updates);
        }
        return $this->verify_updates($updates, $check_only);
    }

    function deactivate_extension($company, $check_only = true)
    {
        return $this->remove_extension($check_only);
    }

    function getModuleConstants(&$data, $opts = array())
    {
        $data['SS_KSF_FA_REP_SALESGROUPS'] = SS_KSF_FA_REP_SALESGROUPS;
    }
}
