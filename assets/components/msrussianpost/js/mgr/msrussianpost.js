var msRussianPost = function (config) {
	config = config || {};
	msRussianPost.superclass.constructor.call(this, config);
};
Ext.extend(msRussianPost, Ext.Component, {
	page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('msrussianpost', msRussianPost);

msRussianPost = new msRussianPost();