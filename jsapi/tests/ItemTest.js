import test from 'ava';
import Item from '../src/Item.js';


test.before(t => {
	t.context.data = {
		'id': 123,
		'type': 'product/property',
		'links': {
			'self': 'http://example.com/jsonapi',
			'next': {
				'href': 'http://example.com/jsonapi/next',
				'allow': ['GET']
			},
		},
		'attributes': {
			'product.property.type': 'package-weight',
			'product.property.languageid': null,
			'product.property.value': '10.00',
		}
	};
});


test('get value', t => {
	const item = new Item(t.context.data);
	t.is('package-weight', item.get('product.property.type'));
});


test('get value short', t => {
	const item = new Item(t.context.data);
	t.is('package-weight', item.get('type'));
});


test('get default value', t => {
	const item = new Item(t.context.data);
	t.is('test', item.get('invalid', 'test'));
});


test('get id', t => {
	const item = new Item(t.context.data);
	t.is(123, item.id);
});


test('get type', t => {
	const item = new Item(t.context.data);
	t.is('product/property', item.type);
});


test('get link', t => {
	const item = new Item(t.context.data);
	t.is('http://example.com/jsonapi', item.link('self'));
});


test('get link from object', t => {
	const item = new Item(t.context.data);
	t.is('http://example.com/jsonapi/next', item.link('next'));
});


test('get all links', t => {
	const item = new Item(t.context.data);
	t.deepEqual( {
		'self': 'http://example.com/jsonapi',
		'next': {
			'href': 'http://example.com/jsonapi/next',
			'allow': ['GET']
		}
	}, item.link());
});


test('get link not allowed', t => {
	const item = new Item(t.context.data);
	t.is(null, item.link('next', 'DELETE'));
});


test('get link for invalid name', t => {
	const item = new Item(t.context.data);
	t.is(null, item.link('invalid'));
});
