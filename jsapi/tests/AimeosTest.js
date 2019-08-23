import test from 'ava';
import Aimeos from '../src/Aimeos.js';
import Manager from '../src/Manager.js';
import RelItem from '../src/RelItem.js';


test('simple content URL', t => {
	const aimeos = new Aimeos({});
	t.is('/path/to/file', aimeos.content('path/to/file'));
});


test('relative content URL', t => {
	const aimeos = new Aimeos({
		'content-baseurl': 'http://localhost/'
	});
	t.is('http://localhost/path/to/file', aimeos.content('path/to/file'));
});


test('absolute content URL', t => {
	const aimeos = new Aimeos({
		'content-baseurl': 'http://localhost/'
	});
	t.is('http://localhost/path/to/file', aimeos.content('http://localhost/path/to/file'));
});


test('data content URL', t => {
	const aimeos = new Aimeos({
		'content-baseurl': 'http://localhost/'
	});
	t.is('data:ABCD', aimeos.content('data:ABCD'));
});


test('create', t => {
	const aimeos = new Aimeos({'prefix': 'ai', 'csrf': {'name': '_token', 'value': 'ABC'}});
	aimeos.axios = {
		'request': function(request) {
			t.is('POST', request.method);
			t.is('http://localhost/', request.url);
			t.deepEqual({'ai': {'a': 1}, '_token': 'ABC'}, request.data);
		},
		'interceptors': {'response': {'use': function(fcn) { t.truthy(typeof fcn === 'function'); }}}
	};
	aimeos.create('http://localhost/', {'a': 1});
});


test('delete', t => {
	const aimeos = new Aimeos({'prefix': 'ai', 'csrf': {'name': '_token', 'value': 'ABC'}});
	aimeos.axios = {
		'request': function(request) {
			t.is('DELETE', request.method);
			t.is('http://localhost/', request.url);
			t.deepEqual({'ai': {'a': 1}, '_token': 'ABC'}, request.data);
		},
		'interceptors': {'response': {'use': function(fcn) { t.truthy(typeof fcn === 'function'); }}}
	};
	aimeos.delete('http://localhost/', {'a': 1});
});


test('get', t => {
	const aimeos = new Aimeos({'prefix': 'ai', 'csrf': {'name': '_token', 'value': 'ABC'}});
	aimeos.axios = {
		'request': function(request) {
			t.is('GET', request.method);
			t.is('http://localhost/', request.url);
			t.deepEqual({'ai': {'a': 1}}, request.data);
		},
		'interceptors': {'response': {'use': function(fcn) { t.truthy(typeof fcn === 'function'); }}}
	};
	aimeos.get('http://localhost/', {'a': 1});
});


test('update', t => {
	const aimeos = new Aimeos({'prefix': 'ai', 'csrf': {'name': '_token', 'value': 'ABC'}});
	aimeos.axios = {
		'request': function(request) {
			t.is('PATCH', request.method);
			t.is('http://localhost/', request.url);
			t.deepEqual({'ai': {'a': 1}, '_token': 'ABC'}, request.data);
		},
		'interceptors': {'response': {'use': function(fcn) { t.truthy(typeof fcn === 'function'); }}}
	};
	aimeos.update('http://localhost/', {'a': 1});
});


test('transform search response total', t => {
	const aimeos = new Aimeos({});
	t.is(10, aimeos.transform('search')({'data': {'meta': {'total': 10}}}).total);
});


test('transform search response links', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({'data': {'links': {'self': 'http://localhost/'}}});
	t.deepEqual({'self': 'http://localhost/'}, result.links);
});


test('transform search response items', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({
		'data': {
			'data': [
				{'id': '1', 'type': 'product', 'attributes': {}},
				{'id': '2', 'type': 'product', 'attributes': {}},
				{'id': '3', 'type': 'product', 'attributes': {}}
			]
		}
	});
	t.is(3, result.data.length);
	for(let item of result.data) {
		t.truthy(item instanceof RelItem);
	}
});


test('transform search response with property relationship', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({
		'data': {
			'data': [
				{'id': '1', 'type': 'product', 'attributes': {}, 'relationships': {
					'product/property': {'data': [{'id': '11', 'type': 'product/property'}]}
				}}
			],
			'included': [
				{'id': '11', 'type': 'product/property', 'attribute': {}}
			]
		}
	});
	t.is(1, result.data[0].getPropertyItems().length);
});


test('transform search response with refitem relationship', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({
		'data': {
			'data': [
				{'id': '1', 'type': 'product', 'attributes': {}, 'relationships': {
					'media': {'data': [{'id': '11', 'type': 'media', 'attributes': {}}]}
				}},
			],
			'included': [
				{'id': '11', 'type': 'media', 'attribute': {}}
			]
		}
	});
	t.is(1, result.data[0].getRelItems('media').length);
});


test('transform search response with refitem relationship without attributes', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({
		'data': {
			'data': [
				{'id': '1', 'type': 'product', 'relationships': {
					'media': {'data': [{'id': '11', 'type': 'media'}]}
				}},
			],
			'included': [
				{'id': '11', 'type': 'media'}
			]
		}
	});
	t.is(1, result.data[0].getRelItems('media').length);
});


test('transform search response with refitem relationship without included item', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({
		'data': {
			'data': [
				{'id': '1', 'type': 'product', 'relationships': {
					'media': {'data': [{'id': '11', 'type': 'media'}]}
				}},
			],
			'included': []
		}
	});
	t.deepEqual([], result.data[0].getRelItems('media'));
});


test('transform search response with refitem relationship without included', t => {
	const aimeos = new Aimeos({});
	const result = aimeos.transform('search')({
		'data': {
			'data': [
				{'id': '1', 'type': 'product', 'relationships': {
					'media': {'data': [{'id': '11', 'type': 'media'}]}
				}},
			]
		}
	});
	t.deepEqual([], result.data[0].getPropertyItems());
});


test('use product manager', t => {
	const aimeos = new Aimeos({'resources': {'product': 'http://localhost/'}});
	t.truthy(aimeos.use('product') instanceof Manager);
});
