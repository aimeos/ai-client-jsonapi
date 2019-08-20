import test from 'ava';
import Item from '../src/Item.js';


test.before(t => {
	t.context.data = {
		'id': 123,
		'type': 'product/property',
		'links': {
			'self': {
				'url': 'http://example.com/jsonapi',
				'allow': ['GET']
			}
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
	t.deepEqual({
		'url': 'http://example.com/jsonapi',
		'allow': ['GET']
	}, item.links('self'));
});


test('get all links', t => {
	const item = new Item(t.context.data);
	t.deepEqual( {
		'self': {
			'url': 'http://example.com/jsonapi',
			'allow': ['GET']
		}
	}, item.links());
});


test('get link for invalid name', t => {
	const item = new Item(t.context.data);
	t.is(null, item.links('invalid'));
});
