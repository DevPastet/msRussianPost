msRussianPost.page.Home = function (config) {
	config = config || {};
	Ext.applyIf(config, {
		components: [{
			xtype: 'msrussianpost-panel-home', renderTo: 'msrussianpost-panel-home-div'
		}]
	});
	msRussianPost.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(msRussianPost.page.Home, MODx.Component);
Ext.reg('msrussianpost-page-home', msRussianPost.page.Home);