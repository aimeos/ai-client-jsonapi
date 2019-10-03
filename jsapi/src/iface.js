const aimeos = new Aimeos({/* OPTIONS result */});

let promise = aimeos.get(url, {}); // from response
let promise = aimeos.post(url, {}); // from response
let promise = aimeos.patch(url, {}); // from response
let promise = aimeos.delete(url, {}); // from response

const product = aimeos.use('product');

let promise = product.search({
	filter: {'&&': [{'==': {'product.type': ['select', 'default']}}, {'>': {'product.status': 0}}]},
	f_listtype: 'promotion', // list type (default, promotion, etc.)
	f_search: 'sneaker', // full text search
	f_catid: 123, // category ID(s)
	f_supid: 456, // supplier ID(s)
	f_attrid: [7,8], // all attributes
	f_optid: [5,6,7], // at least one attribute
	f_oneid: {'color': [1,2,3], 'width': [4,5,6]}, // at least one attribute per type
	fields: 'product.id,product.label', // return only this properties
	sort: '-ctime,product.type', // sort result, "-" prefix for descending
	page: {
		offset: 0,
		limit: 48
	}
}, ['text', 'price', 'media', 'attribute']); // 2nd parameter optional

let promise = product.find('demo-article', ['text', 'price', 'media']); // 2nd parameter optional
let promise = product.get('101', ['text', 'price', 'media']); // 2nd parameter optional

promise.then((items, total, links) => {
	items.forEach(item => {
		let value = item.get('product.type', 'default value'); // 2nd parameter optional
		let refItems = item.getRelItems('attribute', 'color', 'default'); // 2nd/3rd parameter optional
		let propItems = item.getPropertyItems(['package-length', 'package-width']); // parameter optional
		let properties = item.getProperties('package-weight');
	});
});