import test from 'ava';
import Aimeos from '../src/Aimeos.js';
import Manager from '../src/Manager.js';


test('manager create item', t => {
	const aimeos = new Aimeos({});
	aimeos.axios = {
		'request': function(request) { return new Promise((resolve, reject) => {}); },
		'interceptors': {'response': {'use': function(fcn) {}}}
	};
	const manager = new Manager(aimeos, 'http://localhost/', 'product');
	t.truthy(manager.create({}) instanceof Promise);
});


test('manager delete item', t => {
	const aimeos = new Aimeos({});
	aimeos.axios = {
		'request': function(request) { return new Promise((resolve, reject) => {}); },
		'interceptors': {'response': {'use': function(fcn) {}}}
	};
	const manager = new Manager(aimeos, 'http://localhost/', 'product');
	t.truthy(manager.delete({}) instanceof Promise);
});


test('manager find item', t => {
	const aimeos = new Aimeos({});
	aimeos.axios = {
		'request': function(request) { return new Promise((resolve, reject) => {}); },
		'interceptors': {'response': {'use': function(fcn) {}}}
	};
	const manager = new Manager(aimeos, 'http://localhost/', 'product');
	t.truthy(manager.find({}) instanceof Promise);
});


test('manager get item', t => {
	const aimeos = new Aimeos({});
	aimeos.axios = {
		'request': function(request) { return new Promise((resolve, reject) => {}); },
		'interceptors': {'response': {'use': function(fcn) {}}}
	};
	const manager = new Manager(aimeos, 'http://localhost/', 'product');
	t.truthy(manager.get({}) instanceof Promise);
});


test('manager search item', t => {
	const aimeos = new Aimeos({});
	aimeos.axios = {
		'request': function(request) { return new Promise((resolve, reject) => {}); },
		'interceptors': {'response': {'use': function(fcn) {}}}
	};
	const manager = new Manager(aimeos, 'http://localhost/', 'product');
	t.truthy(manager.search({}) instanceof Promise);
});


test('manager update item', t => {
	const aimeos = new Aimeos({});
	aimeos.axios = {
		'request': function(request) { return new Promise((resolve, reject) => {}); },
		'interceptors': {'response': {'use': function(fcn) {}}}
	};
	const manager = new Manager(aimeos, 'http://localhost/', 'product');
	t.truthy(manager.update({}) instanceof Promise);
});
