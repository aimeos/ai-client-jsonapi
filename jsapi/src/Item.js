/**
 * Common item object
 */
export default class Item {

	/**
	 * Initializes the object
	 *
	 * @param {object} data JSON:API document with id, type, links and attributes
	 */
	constructor(data) {
		this.data = data;
		this.prefix = data['type'] && data['type'].replace(/\//, '.') + '.';
	}


	/**
	 * Returns the value associated to the given key or the default value
	 *
	 * @param {string} key Key to return the value for
	 * @param {*} defvalue Default value
	 * @returns Value for the given key or default value if no value is available for the key
	 */
	get(key, defvalue) {
		return this.data['attributes'] &&
			(this.data['attributes'][this.prefix + key] || this.data['attributes'][key]) || defvalue;
	}


	/**
	 * Returns the ID of the item
	 *
	 * @returns Unique ID of the item
	 */
	get id() {
		return this.data['id'] || null;
	}


	/**
	 * Returns the type of the item
	 *
	 * @returns Type of the item
	 */
	get type() {
		return this.data['type'] || null;
	}


	/**
	 * Returns the available links for the item
	 *
	 * @param {string|null} Name of the link or null for all links
	 * @param {string} Method name of the action what should be performed afterwards
	 * @returns Map of link objects, a single URL or null if no URL for that name is available
	 */
	link(name = null, method = 'GET') {

		if(typeof name !== 'string') {
			return this.data['links'] || {};
		}

		if(!(this.data['links'] && this.data['links'][name])) {
			return null;
		}

		if(typeof this.data['links'][name] !== 'object' || this.data['links'][name] === null) {
			return this.data['links'][name];
		}

		if(this.data['links'][name]['allow'] && Array.isArray(this.data['links'][name]['allow'])
			&& this.data['links'][name]['allow'].includes(method) && this.data['links'][name]['href']
		) {
			return this.data['links'][name]['href'];
		}

		return null;
	}
}
