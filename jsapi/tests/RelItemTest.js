import test from 'ava';
import RelItem from '../src/RelItem.js';
import Item from '../src/Item.js';


test.before(t => {
	t.context.data = {
		'id': '123',
		'type': 'product',
		'attributes': {
			'product.type': 'select',
			'product.datestart': null,
			'product.status': 1
		},
		'relationships': {
			'product/property': {
				'data': [{
					'id': '456',
					'type': 'product/property'
				}, {
					'id': '567', // missing property reference
					'type': 'product/property'
				}, {
					// invalid property relationship
				}],
			},
			'media': {
				'data': [{
					'id': '789',
					'type': 'media',
					'attributes': {
						'product.lists.domain': 'media',
						'product.lists.type': 'default'
					}
				},{
					// invalid reference relationship
				}]
			},
			'text': {
				'data': [{
					'id': '987', // missing reference
					'type': 'text',
					'attributes': {
						'product.lists.domain': 'text',
						'product.lists.type': 'default'
					}
				}]
			}
		}
	};
	t.context.included = {
		'media': {
			'789': {
				'id': '789',
				'type': 'media',
				'attributes': {
					'media.type': 'default',
					'media.label': 'test image',
					'media.url': 'path/to/file.jpg'
				},
				'relationships': {
					'media/property': {
						'data': [{
							'id': '101',
							'type': 'media/property'
						}],
					}
				}
			}
		},
		'media/property': {
			'101' : {
				'id': '101',
				'type': 'media/property',
				'attributes': {
					'product.property.type': 'copyright',
					'product.property.languageid': 'en',
					'product.property.value': 'me'
				}
			}
		},
		'product/property': {
			'456' : {
				'id': '456',
				'type': 'product/property',
				'attributes': {
					'product.property.type': 'package-weight',
					'product.property.languageid': null,
					'product.property.value': '10.00'
				}
			}
		}
	};
});


test('get value', t => {
	const item = new RelItem(t.context.data);
	t.is('select', item.get('product.type'));
});


test('get value no "attributes"', t => {
	const item = new RelItem({
		'id': '123',
		'type': 'product'
	});
	t.is('default', item.get('key', 'default'));
});


test('invalid data no "relationships"', t => {
	const item = new RelItem({
		'id': '123',
		'type': 'product',
		'attributes': {
			'product.type': 'select',
			'product.datestart': null,
			'product.status': 1
		}
	});
	t.deepEqual([], item.getPropertyItems());
	t.deepEqual([], item.getRelItems('media'));
});


test('invalid data no "included" text data', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual([], item.getRelItems('text'));
});


test('get properties for "package-weight" type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual(['10.00'], item.getProperties('package-weight'));
});


test('get properties for an invalid type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual([], item.getProperties('invalid'));
});


test('get all property items', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new Item({
		'id': '456',
		'type': 'product/property',
		'attributes': {
			'product.property.type': 'package-weight',
			'product.property.languageid': null,
			'product.property.value': '10.00'
		}
	})];
	t.deepEqual(expected, item.getPropertyItems());
});


test('get property items for "package-weight" type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new Item({
		'id': '456',
		'type': 'product/property',
		'attributes': {
			'product.property.type': 'package-weight',
			'product.property.languageid': null,
			'product.property.value': '10.00'
		}
	})];
	t.deepEqual(expected, item.getPropertyItems('package-weight'));
});


test('get property items for ["package-weight"] type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new Item({
		'id': '456',
		'type': 'product/property',
		'attributes': {
			'product.property.type': 'package-weight',
			'product.property.languageid': null,
			'product.property.value': '10.00'
		}
	})];
	t.deepEqual(expected, item.getPropertyItems(['package-weight']));
});


test('get property items for an invalid type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual([], item.getPropertyItems('invalid'));
});


test('get all refitems', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new RelItem({
		'id': '789',
		'type': 'media',
		'attributes': {
			'media.type': 'default',
			'media.label': 'test image',
			'media.url': 'path/to/file.jpg',
			'product.lists.domain': 'media',
			'product.lists.type': 'default'
		},
		'relationships': {
			'media/property': {
				'data': [{
					'id': '101',
					'type': 'media/property'
				}],
			}
		}
	}, t.context.included)];
	t.deepEqual(expected, item.getRelItems('media'));
});


test('get refitems for "default" type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new RelItem({
		'id': '789',
		'type': 'media',
		'attributes': {
			'media.type': 'default',
			'media.label': 'test image',
			'media.url': 'path/to/file.jpg',
			'product.lists.domain': 'media',
			'product.lists.type': 'default'
		},
		'relationships': {
			'media/property': {
				'data': [{
					'id': '101',
					'type': 'media/property'
				}],
			}
		}
	}, t.context.included)];
	t.deepEqual(expected, item.getRelItems('media', 'default'));
});


test('get refitems for ["default"] type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new RelItem({
		'id': '789',
		'type': 'media',
		'attributes': {
			'media.type': 'default',
			'media.label': 'test image',
			'media.url': 'path/to/file.jpg',
			'product.lists.domain': 'media',
			'product.lists.type': 'default'
		},
		'relationships': {
			'media/property': {
				'data': [{
					'id': '101',
					'type': 'media/property'
				}],
			}
		}
	}, t.context.included)];
	t.deepEqual(expected, item.getRelItems('media', ['default']));
});


test('get refitems for "invalid" type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual([], item.getRelItems('media', 'invalid'));
});


test('get refitems for ["invalid"] type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual([], item.getRelItems('media', ['invalid']));
});


test('get refitems for "default" list type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new RelItem({
		'id': '789',
		'type': 'media',
		'attributes': {
			'media.type': 'default',
			'media.label': 'test image',
			'media.url': 'path/to/file.jpg',
			'product.lists.domain': 'media',
			'product.lists.type': 'default'
		},
		'relationships': {
			'media/property': {
				'data': [{
					'id': '101',
					'type': 'media/property'
				}],
			}
		}
	}, t.context.included)];
	t.deepEqual(expected, item.getRelItems('media', null, 'default'));
});


test('get refitems for ["default"] list type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new RelItem({
		'id': '789',
		'type': 'media',
		'attributes': {
			'media.type': 'default',
			'media.label': 'test image',
			'media.url': 'path/to/file.jpg',
			'product.lists.domain': 'media',
			'product.lists.type': 'default'
		},
		'relationships': {
			'media/property': {
				'data': [{
					'id': '101',
					'type': 'media/property'
				}],
			}
		}
	}, t.context.included)];
	t.deepEqual(expected, item.getRelItems('media', null, ['default']));
});


test('get refitems for "invalid" list type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	t.deepEqual([], item.getRelItems('media', null, 'invalid'));
});


test('get refitems for "default" type and list type', t => {
	const item = new RelItem(t.context.data, t.context.included);
	const expected = [new RelItem({
		'id': '789',
		'type': 'media',
		'attributes': {
			'media.type': 'default',
			'media.label': 'test image',
			'media.url': 'path/to/file.jpg',
			'product.lists.domain': 'media',
			'product.lists.type': 'default'
		},
		'relationships': {
			'media/property': {
				'data': [{
					'id': '101',
					'type': 'media/property'
				}],
			}
		}
	}, t.context.included)];
	t.deepEqual(expected, item.getRelItems('media', 'default', 'default'));
});
