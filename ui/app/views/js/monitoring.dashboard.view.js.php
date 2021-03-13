<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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


/**
 * @var CView $this
 */
?>

<script>
	function initializeView(dashboard, widget_defaults, time_period, dynamic, web_layout_mode) {

		const init = () => {
			timeControl.refreshPage = false;

			ZABBIX.Dashboard = new CDashboard(document.querySelector('.<?= ZBX_STYLE_DASHBRD ?>'), {
				containers: {
					grid: document.querySelector('.<?= ZBX_STYLE_DASHBRD_GRID ?>'),
					navigation: document.querySelector('.<?= ZBX_STYLE_DASHBRD_NAVIGATION ?>'),
					navigation_tabs: document.querySelector('.<?= ZBX_STYLE_DASHBRD_NAVIGATION_TABS ?>')
				},
				buttons: {
					previous_page: document.querySelector('.<?= ZBX_STYLE_DASHBRD_PREVIOUS_PAGE ?>'),
					next_page: document.querySelector('.<?= ZBX_STYLE_DASHBRD_NEXT_PAGE ?>'),
					slideshow: document.querySelector('.<?= ZBX_STYLE_DASHBRD_TOGGLE_SLIDESHOW ?>')
				},
				data: {
					dashboardid: dashboard.dashboardid,
					name: dashboard.name,
					userid: dashboard.owner.id,
					templateid: null,
					display_period: dashboard.display_period,
					auto_start: dashboard.auto_start
				},
				cell_width: 100 / <?= DASHBOARD_MAX_COLUMNS ?>,
				cell_height: 70,
				max_columns: <?= DASHBOARD_MAX_COLUMNS ?>,
				max_rows: <?= DASHBOARD_MAX_ROWS ?>,
				widget_min_rows: <?= DASHBOARD_WIDGET_MIN_ROWS ?>,
				widget_max_rows: <?= DASHBOARD_WIDGET_MAX_ROWS ?>,
				widget_defaults: widget_defaults,
				is_editable: dashboard.can_edit_dashboards && dashboard.editable
					&& web_layout_mode != <?= ZBX_LAYOUT_KIOSKMODE ?>,
				is_edit_mode: dashboard.dashboardid === null,
				can_edit_dashboards: dashboard.can_edit_dashboards,
				is_kiosk_mode: web_layout_mode == <?= ZBX_LAYOUT_KIOSKMODE ?>,
				time_period: time_period,
				dynamic_hostid: dynamic.host ? dynamic.host.id : null
			});

			for (const page of dashboard.pages) {
				for (const widget of page.widgets) {
					widget.fields = (typeof widget.fields === 'object') ? widget.fields : {};
					widget.configuration = (typeof widget.configuration === 'object') ? widget.configuration : {};
				}

				ZABBIX.Dashboard.addDashboardPage(page);
			}

			ZABBIX.Dashboard.activate();

			if (web_layout_mode != <?= ZBX_LAYOUT_KIOSKMODE ?>) {
				ZABBIX.Dashboard.on(DASHBOARD_EVENT_EDIT, edit);
				ZABBIX.Dashboard.on(DASHBOARD_EVENT_APPLY_PROPERTIES, events.applyProperties);

				if (dynamic.has_dynamic_widgets) {
					$('#dynamic_hostid').on('change', events.dynamicHostChange);
				}

				if (dashboard.dashboardid === null) {
					edit();
					ZABBIX.Dashboard.editProperties();
				}
				else {
					document
						.getElementById('dashbrd-edit')
						.addEventListener('click', () => {
							ZABBIX.Dashboard.setEditMode();
							edit();
						});
				}
			}

			if (dynamic.has_dynamic_widgets) {
				// Perform dynamic host switch when browser back/previous buttons are pressed.
				window.addEventListener('popstate', events.popState);
			}

			jqBlink.blink();
		};

		const edit = () => {
			timeControl.disableAllSBox();

			if (dynamic.has_dynamic_widgets) {
				$('#dynamic_hostid').off('change', events.dynamicHostChange);
			}

			document
				.querySelectorAll('.filter-space')
				.forEach((el) => {
					el.style.display = 'none';
				});

			clearMessages();

			document
				.querySelectorAll('#dashbrd-control > li')
				.forEach((el) => {
					el.style.display = (el.nextElementSibling === null) ? '' : 'none';
				});

			document
				.getElementById('dashbrd-config')
				.addEventListener('click', () => ZABBIX.Dashboard.editProperties());

			document
				.getElementById('dashbrd-add-widget')
				.addEventListener('click', () => ZABBIX.Dashboard.addNewWidget());

			document
				.getElementById('dashbrd-add')
				.addEventListener('click', events.addClick);

//			document
//				.getElementById('dashbrd-save')
//				.addEventListener('click', () => save());

			document
				.getElementById('dashbrd-cancel')
				.addEventListener('click', (e) => {
					cancelEditing();
					e.preventDefault();
				}
			);

//			$.subscribe('dashboard.grid.busy', (e, data) => {
//				is_busy = data.state;
//				updateBusy();
//			});


			enableNavigationWarning();
		};

		const save = () => {
			clearMessages();

			ZABBIX.Dashboard.saveDashboard();

			const ajax_data = {
				dashboardid: (dashboard.dashboardid !== null) ? dashboard.dashboardid : undefined,
				userid: dashboard.owner.id,
				name: dashboard.name,
				display_period: dashboard.display_period,
				auto_start: dashboard.auto_start,
				widgets: [],
				sharing: dashboard.sharing
			};

			for (const widget of ZABBIX.Dashboard.getWidgets()) {
				const ajax_widget = {};

				if (widget.widgetid !== '') {
					ajax_widget.widgetid = widget.widgetid;
				}
				ajax_widget.pos = widget.pos;
				ajax_widget.type = widget.type;
				ajax_widget.name = widget.name;
				ajax_widget.view_mode = widget.view_mode;
				if (Object.keys(widget.fields).length != 0) {
					ajax_widget.fields = JSON.stringify(widget.fields);
				}

				ajax_data.widgets.push(ajax_widget);
			}

			is_busy_saving = true;
			updateBusy();

			const url = new Curl('zabbix.php');

			url.setArgument('action', 'dashboard.update');

			$.ajax({
				url: url.getUrl(),
				data: ajax_data,
				dataType: 'json',
				method: 'POST'
			})
				.always(() => {
					is_busy_saving = false;
					updateBusy();
				})
				.then((response) => {
					if ('redirect' in response) {
						if ('system-message-ok' in response) {
							postMessageOk(response['system-message-ok']);
						}

						disableNavigationWarning();

						location.replace(response.redirect);
					}
					else if ('errors' in response) {
						addMessage(response.errors);
					}
				});
		};

		const updateBusy = () => {
			document.getElementById('dashbrd-save').disabled = is_busy || is_busy_saving;
		};

		const cancelEditing = () => {
			const curl = new Curl('zabbix.php', false);

			curl.setArgument('action', 'dashboard.view');

			if (dashboard.dashboardid !== null) {
				curl.setArgument('dashboardid', dashboard.dashboardid);
			}
			else {
				curl.setArgument('cancel', '1');
			}

			location.replace(curl.getUrl());
		};

		const enableNavigationWarning = () => {
			window.addEventListener('beforeunload', events.beforeUnload, {passive: false});
		};

		const disableNavigationWarning = () => {
			window.removeEventListener('beforeunload', events.beforeUnload);
		};

		const events = {
			addClick: (e) => {
				const menu = [
					{
						items: [
							{
								label: t('Add widget'),
								clickCallback: () => ZABBIX.Dashboard.addNewWidget()
							},
							{
								label: t('Add page'),
								clickCallback: () => ZABBIX.Dashboard.addNewDashboardPage()
							}
						]
					},
					{
						items: [
							{
								label: t('Paste widget'),
								clickCallback: () => ZABBIX.Dashboard.pasteWidget(null, null),
								disabled: (ZABBIX.Dashboard.getStoredWidgetCopy() === null)
							},
							{
								label: t('Paste page'),
								clickCallback: () => ZABBIX.Dashboard.pastePage(),
								disabled: true
							}
						]
					}
				];

				$(e.target).menuPopup(menu, new jQuery.Event(e), {
					position: {
						of: e.target,
						my: 'left top',
						at: 'left bottom'
					}
				});
			},

			beforeUnload: (e) => {
				if (ZABBIX.Dashboard.isUpdated()) {
					// Display confirmation message.
					e.preventDefault();
					e.returnValue = '';
				}
			},

			popState: (e) => {
				const host = (e.state !== null && 'host' in e.state) ? e.state.host : null;

				$('#dynamic_hostid').multiSelect('addData', host ? [host] : [], false);

				ZABBIX.Dashboard.setDynamicHost(host ? host.id : null);
			},

			dynamicHostChange: () => {
				const hosts = $('#dynamic_hostid').multiSelect('getData');
				const host = hosts.length ? hosts[0] : null;
				const curl = new Curl('zabbix.php', false);

				curl.setArgument('action', 'dashboard.view');

				if (dashboard.dashboardid !== null) {
					curl.setArgument('dashboardid', dashboard.dashboardid);
				}

				if (time_period !== null) {
					curl.setArgument('from', time_period.from);
					curl.setArgument('to', time_period.to);
				}

				if (host !== null) {
					curl.setArgument('hostid', host.id);
				}

				ZABBIX.Dashboard.setDynamicHost(host ? host.id : null);

				history.pushState({host: host}, '', curl.getUrl());

				updateUserProfile('web.dashbrd.hostid', host ? host.id : 1);
			},

			applyProperties: () => {
				const dashboard_data = ZABBIX.Dashboard.getData();

				document.getElementById('<?= ZBX_STYLE_PAGE_TITLE ?>').textContent = dashboard_data.name;
				document.getElementById('dashboard-direct-link').textContent = dashboard_data.name;
			}
		};

//		let is_busy = false;
//		let is_busy_saving = false;

		init();
	}

	function initializeDashboardShare(data) {
		window.dashboard_share = new DashboardShare(data);
		window.dashboard_share.live();

		/**
		 * @see init.js add.popup event
		 */
		window.addPopupValues = function(list) {
			dashboard_share.addPopupValues(list);
		}
	}

	/**
	 * Find and refresh widget responsible for launching the "Update problem" popup after it was submitted.
	 *
	 * @param {String} type      Widget type to search for.
	 * @param {object} response  The response object from the "acknowledge.create" action.
	 * @param {object} overlay   The overlay object of the "Update problem" popup form.
	 */

	// TODO fix this shame.
	function refreshWidgetOnAcknowledgeCreate(type, response, overlay) {
		var handle_selector = '.dashbrd-grid-widget-content',
			handle = overlay.trigger_parents.filter(handle_selector).get(0);

		if (!handle) {
			var dialogue = overlay.trigger_parents.filter('.overlay-dialogue');

			if (dialogue.length) {
				var dialogue_overlay = overlays_stack.getById(dialogue.data('hintboxid'));
				if (dialogue_overlay && dialogue_overlay.type === 'hintbox') {
					handle = dialogue_overlay.element.closest(handle_selector);
				}
			}
		}

		if (handle) {
			var widgets = ZABBIX.Dashboard.getWidgetsBy('type', type);

			widgets.forEach(widget => {
				if ($.contains(widget.container[0], handle)) {
					for (var i = overlays_stack.length - 1; i >= 0; i--) {
						var hintbox = overlays_stack.getById(overlays_stack.stack[i]);
						if (hintbox.type === 'hintbox') {
							hintbox_handle = hintbox.element.closest(handle_selector);
							if ($.contains(widget.container[0], hintbox_handle)) {
								hintBox.hideHint(hintbox.element, true);
							}
						}
					}

					clearMessages();
					addMessage(makeMessageBox('good', [], response.message, true, false));

					ZABBIX.Dashboard.refreshWidget(widget.uniqueid);
				}
			});
		}
	}
</script>
