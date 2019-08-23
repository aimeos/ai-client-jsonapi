import Aimeos from './Aimeos.js';


/**
 * Common manager implementation
 */
export default class Manager {

	/**
	 * Initializes the object
	 *
	 * @param {object} meta Key/value pairs of meta data with resource endpoints
	 */
	constructor(aimeos, url, resource) {

		if(!(aimeos instanceof Aimeos)) {
			throw new TypeError('Parameter is not an Aimeos object');
		}

		this.resource = resource;
		this.aimeos = aimeos;
		this.url = url;
	}


	/**
	 * Creates a new item
	 *
	 * @param {object} prop Key/value pairs of the item properties
	 * @returns Promise
	 */
	create(prop) {
		return this.aimeos.create(this.url, {'data': {'attributes': prop}});
	}


	/**
	 * Deletes an existing item by its ID
	 *
	 * @param {string} id Unique ID of the item or list of IDs
	 * @returns Promise
	 */
	delete(id) {
		return this.aimeos.delete(this.url, {'data': {'id': id}});
	}


	/**
	 * Returns a single item identified by its code
	 *
	 * @param {string} code Unique code of the item
	 * @param {array} domains Referenced items from other domains that should be fetched too
	 * @returns Promise object with "data", "links" and "total" keys
	 */
	find(code, domains = []) {

		let condition = {};
		condition[this.resource.replace('/', '.') + '.code'] = code;

		let filter = {
			'filter': {'==': condition},
			'include': Array.isArray(domains) ? domains.join(',') : ''
		}

		return this.aimeos.get(this.url, filter);
	}


	/**
	 * Returns a single item identified by its ID
	 *
	 * @param {string} id Unique ID of the item
	 * @param {array} domains Referenced items from other domains that should be fetched too
	 * @returns Promise object with "data", "links" and "total" keys
	 */
	get(id, domains = []) {

		let filter = {
			'id': id,
			'include': Array.isArray(domains) ? domains.join(',') : ''
		}

		return this.aimeos.get(this.url, filter);
	}


	/**
	 * Returns a list of items filtered by the given conditions
	 *
	 * @param {object} filter Filter object
	 * @param {array} domains Referenced items from other domains that should be fetched too
	 * @returns Promise object with "data", "links" and "total" keys
	 */
	search(filter, domains = []) {

		filter['include'] = Array.isArray(domains) ? domains.join(',') : '';
		return this.aimeos.get(this.url, filter);
	}


	/**
	 * Updates an existing item by its ID
	 *
	 * @param {string} id Unique ID of the item
	 * @param {object} prop Key/value pairs of the item properties
	 * @returns Promise
	 */
	update(id, prop) {
		return this.aimeos.update(this.url, {'data': {'id': id, 'attributes': prop}});
	}
}