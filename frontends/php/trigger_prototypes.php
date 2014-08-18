<?php
/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/hosts.inc.php';
require_once dirname(__FILE__).'/include/triggers.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

$page['title'] = _('Configuration of trigger prototypes');
$page['file'] = 'trigger_prototypes.php';
$page['hist_arg'] = array('hostid', 'parent_discoveryid');

require_once dirname(__FILE__).'/include/page_header.php';

//	VAR		TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'parent_discoveryid' => array(T_ZBX_INT, O_MAND, P_SYS,	DB_ID,		null),
	'hostid' =>				array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'triggerid' =>			array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		'(isset({form})&&({form}=="update"))'),
	'copy_type' =>			array(T_ZBX_INT, O_OPT, P_SYS,	IN('0,1'),	'isset({copy})'),
	'copy_mode' =>			array(T_ZBX_INT, O_OPT, P_SYS,	IN('0'),	null),
	'type' =>				array(T_ZBX_INT, O_OPT, null,	IN('0,1'),	null),
	'description' =>		array(T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'isset({save})', _('Name')),
	'expression' =>			array(T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'isset({save})', _('Expression')),
	'priority' =>			array(T_ZBX_INT, O_OPT, null,	IN('0,1,2,3,4,5'), 'isset({save})'),
	'comments' =>			array(T_ZBX_STR, O_OPT, null,	null,		'isset({save})'),
	'url' =>				array(T_ZBX_STR, O_OPT, null,	null,		'isset({save})'),
	'status' =>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'input_method' =>		array(T_ZBX_INT, O_OPT, null,	NOT_EMPTY,	'isset({toggle_input_method})'),
	'expr_temp' =>			array(T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'(isset({add_expression})||isset({and_expression})||isset({or_expression})||isset({replace_expression}))'),
	'expr_target_single' =>	array(T_ZBX_STR, O_OPT, null,	NOT_EMPTY,	'(isset({and_expression})||isset({or_expression})||isset({replace_expression}))'),
	'dependencies' =>		array(T_ZBX_INT, O_OPT, null,	DB_ID,		null),
	'new_dependence' =>		array(T_ZBX_INT, O_OPT, null,	DB_ID.'{}>0', 'isset({add_dependence})'),
	'rem_dependence' =>		array(T_ZBX_INT, O_OPT, null,	DB_ID,		null),
	'g_triggerid' =>		array(T_ZBX_INT, O_OPT, null,	DB_ID,		null),
	'copy_targetid' =>		array(T_ZBX_INT, O_OPT, null,	DB_ID,		null),
	'filter_groupid' =>		array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		'isset({copy})&&(isset({copy_type})&&({copy_type}==0))'),
	'showdisabled' =>		array(T_ZBX_INT, O_OPT, P_SYS,	IN('0,1'),	null),
	// actions
	'massupdate' =>			array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'visible' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	'go' =>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'toggle_input_method' =>array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'add_expression' => 	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'and_expression' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'or_expression' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'replace_expression' =>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'remove_expression' =>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'test_expression' =>	array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'add_dependence' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'del_dependence' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'group_enable' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'group_disable' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'group_delete' =>		array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'copy' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'clone' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'save' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'mass_save' =>			array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'delete' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,	null),
	'cancel' =>				array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'form' =>				array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'form_refresh' =>		array(T_ZBX_INT, O_OPT, null,	null,		null),
	// sort and sortorder
	'sort' =>				array(T_ZBX_STR, O_OPT, P_SYS, IN("'priority','description','status'"),		null),
	'sortorder' =>			array(T_ZBX_STR, O_OPT, P_SYS, IN("'".ZBX_SORT_DOWN."','".ZBX_SORT_UP."'"),	null)
);
$_REQUEST['showdisabled'] = getRequest('showdisabled', CProfile::get('web.triggers.showdisabled', 1));

check_fields($fields);
validate_sort_and_sortorder('description', ZBX_SORT_UP);

$_REQUEST['status'] = isset($_REQUEST['status']) ? TRIGGER_STATUS_ENABLED : TRIGGER_STATUS_DISABLED;
$_REQUEST['type'] = isset($_REQUEST['type']) ? TRIGGER_MULT_EVENT_ENABLED : TRIGGER_MULT_EVENT_DISABLED;
$_REQUEST['go'] = getRequest('go', 'none');

// validate permissions
if (getRequest('parent_discoveryid')) {
	$discovery_rule = API::DiscoveryRule()->get(array(
		'itemids' => $_REQUEST['parent_discoveryid'],
		'output' => API_OUTPUT_EXTEND,
		'editable' => true,
		'preservekeys' => true
	));
	$discovery_rule = reset($discovery_rule);
	if (!$discovery_rule) {
		access_deny();
	}

	if (isset($_REQUEST['triggerid'])) {
		$triggerPrototype = API::TriggerPrototype()->get(array(
			'triggerids' => $_REQUEST['triggerid'],
			'output' => array('triggerid'),
			'editable' => true,
			'preservekeys' => true
		));
		if (empty($triggerPrototype)) {
			access_deny();
		}
	}
}
else {
	access_deny();
}

$showdisabled = getRequest('showdisabled', 0);
CProfile::update('web.triggers.showdisabled', $showdisabled, PROFILE_TYPE_INT);

/*
 * Actions
 */
$exprAction = null;
if (isset($_REQUEST['add_expression'])) {
	$_REQUEST['expression'] = $_REQUEST['expr_temp'];
	$_REQUEST['expr_temp'] = '';
}
elseif (isset($_REQUEST['and_expression'])) {
	$exprAction = 'and';
}
elseif (isset($_REQUEST['or_expression'])) {
	$exprAction = 'or';
}
elseif (isset($_REQUEST['replace_expression'])) {
	$exprAction = 'r';
}
elseif (getRequest('remove_expression')) {
	$exprAction = 'R';
	$_REQUEST['expr_target_single'] = $_REQUEST['remove_expression'];
}
elseif (isset($_REQUEST['clone']) && isset($_REQUEST['triggerid'])) {
	unset($_REQUEST['triggerid']);
	$_REQUEST['form'] = 'clone';
}
elseif (hasRequest('save')) {
	$trigger = array(
		'expression' => $_REQUEST['expression'],
		'description' => $_REQUEST['description'],
		'type' => $_REQUEST['type'],
		'priority' => $_REQUEST['priority'],
		'status' => $_REQUEST['status'],
		'comments' => $_REQUEST['comments'],
		'url' => $_REQUEST['url'],
		'flags' => ZBX_FLAG_DISCOVERY_PROTOTYPE
	);

	if (hasRequest('triggerid')) {
		$trigger['triggerid'] = getRequest('triggerid');
		$result = API::TriggerPrototype()->update($trigger);

		show_messages($result, _('Trigger prototype updated'), _('Cannot update trigger prototype'));
	}
	else {
		$result = API::TriggerPrototype()->create($trigger);

		show_messages($result, _('Trigger prototype added'), _('Cannot add trigger prototype'));
	}

	if ($result) {
		unset($_REQUEST['form']);
		uncheckTableRows(getRequest('parent_discoveryid'));
	}

	unset($_REQUEST['save']);
}
elseif (hasRequest('delete') && hasRequest('triggerid')) {
	$result = API::TriggerPrototype()->delete(array(getRequest('triggerid')));

	if ($result) {
		unset($_REQUEST['form'], $_REQUEST['triggerid']);
		uncheckTableRows(getRequest('parent_discoveryid'));
	}
	show_messages($result, _('Trigger prototype deleted'), _('Cannot delete trigger prototype'));
}
elseif (getRequest('go') == 'massupdate' && hasRequest('mass_save') && hasRequest('g_triggerid')) {
	$triggerIds = getRequest('g_triggerid');
	$visible = getRequest('visible');

	if (isset($visible['priority'])) {
		$priority = getRequest('priority');

		foreach ($triggerIds as $triggerId) {
			$result = API::TriggerPrototype()->update(array(
				'triggerid' => $triggerId,
				'priority' => $priority
			));
			if (!$result) {
				break;
			}
		}
	}
	else {
		$result = true;
	}

	if ($result) {
		unset($_REQUEST['massupdate'], $_REQUEST['form'], $_REQUEST['g_triggerid']);
		uncheckTableRows(getRequest('parent_discoveryid'));
	}
	show_messages($result, _('Trigger prototypes updated'), _('Cannot update trigger prototypes'));
}
elseif (str_in_array(getRequest('go'), array('activate', 'disable')) && hasRequest('g_triggerid')) {
	$enable = (getRequest('go') == 'activate');
	$status = $enable ? TRIGGER_STATUS_ENABLED : TRIGGER_STATUS_DISABLED;
	$update = array();

	// get requested triggers with permission check
	$dbTriggerPrototypes = API::TriggerPrototype()->get(array(
		'output' => array('triggerid', 'status'),
		'triggerids' => getRequest('g_triggerid'),
		'editable' => true
	));

	if ($dbTriggerPrototypes) {
		foreach ($dbTriggerPrototypes as $dbTriggerPrototype) {
			$update[] = array(
				'triggerid' => $dbTriggerPrototype['triggerid'],
				'status' => $status
			);
		}

		$result = API::TriggerPrototype()->update($update);
	}
	else {
		$result = true;
	}

	if ($result) {
		uncheckTableRows(getRequest('parent_discoveryid'));
	}

	$updated = count($update);

	$messageSuccess = $enable
		? _n('Trigger prototype enabled', 'Trigger prototypes enabled', $updated)
		: _n('Trigger prototype disabled', 'Trigger prototypes disabled', $updated);
	$messageFailed = $enable
		? _n('Cannot enable trigger prototype', 'Cannot enable trigger prototypes', $updated)
		: _n('Cannot disable trigger prototype', 'Cannot disable trigger prototypes', $updated);

	show_messages($result, $messageSuccess, $messageFailed);
}
elseif (getRequest('go') == 'delete' && hasRequest('g_triggerid')) {
	$result = API::TriggerPrototype()->delete(getRequest('g_triggerid'));

	if ($result) {
		uncheckTableRows(getRequest('parent_discoveryid'));
	}
	show_messages($result, _('Trigger prototypes deleted'), _('Cannot delete trigger prototypes'));
}

/*
 * Display
 */
if ($_REQUEST['go'] == 'massupdate' && isset($_REQUEST['g_triggerid'])) {
	$triggersView = new CView('configuration.triggers.massupdate', getTriggerMassupdateFormData());
	$triggersView->render();
	$triggersView->show();
}
elseif (isset($_REQUEST['form'])) {
	$triggersView = new CView('configuration.triggers.edit', getTriggerFormData($exprAction));
	$triggersView->render();
	$triggersView->show();
}
else {
	$data = array(
		'parent_discoveryid' => getRequest('parent_discoveryid'),
		'showInfoColumn' => false,
		'discovery_rule' => $discovery_rule,
		'hostid' => getRequest('hostid'),
		'showdisabled' => getRequest('showdisabled', 1),
		'triggers' => array()
	);
	CProfile::update('web.triggers.showdisabled', $data['showdisabled'], PROFILE_TYPE_INT);

	// get triggers
	$sortfield = getPageSortField('description');
	$options = array(
		'editable' => true,
		'output' => array('triggerid'),
		'discoveryids' => $data['parent_discoveryid'],
		'sortfield' => $sortfield,
		'limit' => $config['search_limit'] + 1
	);
	if (empty($data['showdisabled'])) {
		$options['filter']['status'] = TRIGGER_STATUS_ENABLED;
	}
	$data['triggers'] = API::TriggerPrototype()->get($options);

	// paging
	$data['paging'] = getPagingLine($data['triggers']);

	$data['triggers'] = API::TriggerPrototype()->get(array(
		'triggerids' => zbx_objectValues($data['triggers'], 'triggerid'),
		'output' => API_OUTPUT_EXTEND,
		'selectHosts' => API_OUTPUT_EXTEND,
		'selectItems' => array('itemid', 'hostid', 'key_', 'type', 'flags', 'status'),
		'selectFunctions' => API_OUTPUT_EXTEND
	));
	order_result($data['triggers'], $sortfield, getPageSortOrder());

	// get real hosts
	$data['realHosts'] = getParentHostsByTriggers($data['triggers']);

	// render view
	$triggersView = new CView('configuration.triggers.list', $data);
	$triggersView->render();
	$triggersView->show();
}

require_once dirname(__FILE__).'/include/page_footer.php';
