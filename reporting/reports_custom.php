<?php
/**
 * Register Sales Group reports with native FA reporting framework.
 */
global $reports, $dim;

$reports->addReportClass(_('Sales Group'), RC_CUSTOMER);

$reports->addReport(RC_CUSTOMER, '_salesgroups_v1', _('Sales Groups & Branches'),
    array(
        _('Group Filter') => 'SELECT',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

$reports->addReport(RC_CUSTOMER, '_salesgroups_v2', _('Sales Groups — Full Detail'),
    array(
        _('Customer') => 'CUSTOMERS',
        _('Group Filter') => 'SELECT',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));
