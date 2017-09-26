msRussianPost.panel.Home = function (config) {
	config = config || {};
	Ext.apply(config, {
		baseCls: 'modx-formpanel',
		layout: 'anchor',
		/*
		 stateful: true,
		 stateId: 'msrussianpost-panel-home',
		 stateEvents: ['tabchange'],
		 getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
		 */
		hideMode: 'offsets',
		items: [{
			html: '<h2>' + _('msrussianpost') + '</h2>',
			cls: '',
			style: {margin: '15px 0'}
		}, {
			xtype: 'modx-tabs',
			defaults: {border: false, autoHeight: true},
			border: true,
			hideMode: 'offsets',
			items: [{
				title: _('msrussianpost_items'),
				layout: 'anchor',
				items: [{
					html: _('msrussianpost_intro_msg'),
					cls: 'panel-desc',
				}, {
					xtype: 'msrussianpost-grid-items',
					cls: 'main-wrapper',
				}]
			}]
		}]
	});
	msRussianPost.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(msRussianPost.panel.Home, MODx.Panel);
Ext.reg('msrussianpost-panel-home', msRussianPost.panel.Home);
