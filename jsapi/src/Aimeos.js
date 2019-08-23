import axios from 'axios';
import Manager from './Manager.js';
import RelItem from './RelItem.js';


/**
 * Aimeos API implementation
 */
export default class Aimeos {

	/**
	 * Initializes the object
	 *
	 * @param {object} meta Key/value pairs of meta data with resource endpoints
	 */
	constructor(meta) {

		this.meta = meta || {};

		this.trans = {
			'search': function(response) {

				let items = [];
				let included = [];
				const data = response.data || {};
				const links = data['links'] || {};
				const total = data['meta'] && data['meta']['total'] || null;

				meta['csrf'] = data['csrf'] || meta['csrf'];

				if(data.included && Array.isArray(data.included)) {

					for(let entry of response.data.included) {

						if(!included[entry['type']]) {
							included[entry['type']] = {}
						}
						included[entry['type']][entry['id']] = entry;
					}
				}

				if(data.data && Array.isArray(data.data)) {

					for(let entry of data.data) {
						items.push(new RelItem(entry, included));
					}
				}

				return {'data': items, 'links': links, 'total': total};
			}
		};
	}


	/**
	 * Returns the ID of the item
	 *
	 * @param {string} path Absolute or relative paths to content on the server or data URIs
	 * @throws TypeError If the given path isn't a string
	 */
	content(path) {

		if(!(typeof path === 'string' || path instanceof String)) {
			throw new TypeError('Parameter is not a string');
		}

		if(path.startsWith('data:') || path.indexOf('://') != -1) {
			return path;
		}

		return (this.meta['content-baseurl'] || '/') + path;
	}


	/**
	 * Creates one or more new resource item
	 *
	 * @param {string} url URL of the resource endpoint
	 * @param {object} params Data object containing the properties of the new item
	 * @returns Promise
	 */
	create(url, params = {}) {

		let data = {};
		this.meta.prefix ? data[this.meta.prefix] = params : params;

		if(this.meta.csrf && this.meta.csrf.name && this.meta.csrf.value) {
			data[this.meta.csrf.name] = this.meta.csrf.value;
		}

		const xhr = this.axios || axios.create({});
		xhr.interceptors.response.use(this.transform('create'));

		return xhr.request({'method': 'POST', 'url': url, 'data': data});
	}


	/**
	 * Deletes one or more existing resource items
	 *
	 * @param {string} url URL of the resource endpoint
	 * @param {object} params Data object containing the IDs of the items to delete
	 * @returns Promise
	 */
	delete(url, params = {}) {

		let data = {};
		this.meta.prefix ? data[this.meta.prefix] = params : params;

		if(this.meta.csrf && this.meta.csrf.name && this.meta.csrf.value) {
			data[this.meta.csrf.name] = this.meta.csrf.value;
		}

		const xhr = this.axios || axios.create({});
		xhr.interceptors.response.use(this.transform('delete'));

		return xhr.request({'method': 'DELETE', 'url': url, 'data': data});
	}


	/**
	 * Retrieves one or more existing resource items
	 *
	 * @param {string} url URL of the resource endpoint
	 * @param {object} params Data object containing the parameter to filter the items
	 * @returns Promise
	 */
	get(url, params = {}) {

		let data = {};
		this.meta.prefix ? data[this.meta.prefix] = params : params;

		const xhr = this.axios || axios.create({});
		xhr.interceptors.response.use(this.transform('get'));

		return xhr.request({'method': 'GET', 'url': url, 'data': data});
	}


	/**
	 * Updates one or more existing resource item
	 *
	 * @param {string} url URL of the resource endpoint
	 * @param {object} params Data object containing the updated properties of the items
	 * @returns Promise
	 */
	update(url, params = {}) {

		let data = {};
		this.meta.prefix ? data[this.meta.prefix] = params : params;

		if(this.meta.csrf && this.meta.csrf.name && this.meta.csrf.value) {
			data[this.meta.csrf.name] = this.meta.csrf.value;
		}

		const xhr = this.axios || axios.create({});
		xhr.interceptors.response.use(this.transform('update'));

		return xhr.request({'method': 'PATCH', 'url': url, 'data': data});
	}


	/**
	 * Sets or returns the transformation function for the response from the server
	 *
	 * @param {string} name Name of the API method the function should be applied to
	 * @param {function} fcn Function with response object as parameter that returns the transformed response
	 * @returns function|Aimeos Transformation function or Aimeos object when function is set
	 */
	transform(name, fcn = null) {

		if(typeof fcn === 'function') {
			this.trans[name] = fcn;
			return this;
		}

		return this.trans[name] || function(response) { return response; };
	}


	/**
	 * Returns a new resource object for interacting with the server
	 *
	 * @param {string} resource Resource key from meta section
	 * @throws TypeError If the resource is not available
	 */
	use(resource) {

		if(this.meta['resources'] && this.meta['resources'][resource]) {
			return new Manager(this, this.meta['resources'][resource], resource);
		}

		throw new TypeError("Resource {resource} not found");
	}
}
