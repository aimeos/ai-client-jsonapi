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
	}


	/**
	 * Returns the value associated to the given key or the default value
	 *
	 * @param {string} key Key to return the value for
	 * @param {*} defvalue Default value
	 * @returns Value for the given key or default value if no value is available for the key
	 */
	get(key, defvalue) {
		return this.data['attributes'][key] || defvalue;
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
	 * @returns Link object, map of link objects or null if no link for that name is available
	 */
	links(name = null) {
		return name ? this.data['links'][name] || null : this.data['links'] || {};
	}
}
