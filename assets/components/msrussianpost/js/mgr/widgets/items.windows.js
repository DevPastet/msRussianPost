msRussianPost.window.CreateItem = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'msrussianpost-item-window-create';
	}
	Ext.applyIf(config, {
		title: _('msrussianpost_item_create'),
		width: 550,
		autoHeight: true,
		url: msRussianPost.config.connector_url,
		action: 'mgr/item/create',
		fields: this.getFields(config),
		keys: [{
			key: Ext.EventObject.ENTER, shift: true, fn: function () {
				this.submit()
			}, scope: this
		}]
	});
	msRussianPost.window.CreateItem.superclass.constructor.call(this, config);
};
Ext.extend(msRussianPost.window.CreateItem, MODx.Window, {

	getFields: function (config) {
		return [{
			xtype: 'textfield',
			fieldLabel: _('msrussianpost_item_name'),
			name: 'name',
			id: config.id + '-name',
			anchor: '99%',
			allowBlank: false,
		}, {
			xtype: 'textarea',
			fieldLabel: _('msrussianpost_item_description'),
			name: 'description',
			id: config.id + '-description',
			height: 150,
			anchor: '99%'
		}, {
			xtype: 'xcheckbox',
			boxLabel: _('msrussianpost_item_active'),
			name: 'active',
			id: config.id + '-active',
			checked: true,
		}];
	}

});
Ext.reg('msrussianpost-item-window-create', msRussianPost.window.CreateItem);


msRussianPost.window.UpdateItem = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'msrussianpost-item-window-update';
	}
	Ext.applyIf(config, {
		title: _('msrussianpost_item_update'),
		width: 550,
		autoHeight: true,
		url: msRussianPost.config.connector_url,
		action: 'mgr/item/update',
		fields: this.getFields(config),
		keys: [{
			key: Ext.EventObject.ENTER, shift: true, fn: function () {
				this.submit()
			}, scope: this
		}]
	});
	msRussianPost.window.UpdateItem.superclass.constructor.call(this, config);
};
Ext.extend(msRussianPost.window.UpdateItem, MODx.Window, {

	getFields: function (config) {
		return [{
			xtype: 'hidden',
			name: 'id',
			id: config.id + '-id',
		}, {
			xtype: 'textfield',
			fieldLabel: _('msrussianpost_item_name'),
			name: 'name',
			id: config.id + '-name',
			anchor: '99%',
			allowBlank: false,
		}, {
			xtype: 'textarea',
			fieldLabel: _('msrussianpost_item_description'),
			name: 'description',
			id: config.id + '-description',
			anchor: '99%',
			height: 150,
		}, {
			xtype: 'xcheckbox',
			boxLabel: _('msrussianpost_item_active'),
			name: 'active',
			id: config.id + '-active',
		}];
	}

});
Ext.reg('msrussianpost-item-window-update', msRussianPost.window.UpdateItem);