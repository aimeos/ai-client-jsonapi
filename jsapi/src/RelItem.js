import Item from './Item.js';


/**
 * Item object with referenced items and properties
 */
export default class RelItem extends Item {

	/**
	 * Initializes the object
	 *
	 * @param {object} data JSON:API document with id, type, attributes and relationships
	 * @param {object} included Nested map of type/id/data e.g. {'text':{'123':{...}}}
	 */
	constructor(data, included) {

		super(data);

		this.included = included || {};
		this.relations = data['relationships'] || {};
	}


	/**
	 * Returns the list of property values for the given type
	 *
	 * @param {string} type Property type
	 * @returns List of property values
	 */
	getProperties(type) {

		let result = [];
		const prefix = super.type + '.property.';
		const domain = super.type + '/property';

		for(let ref of (this.relations[domain]['data'] || [])) {

			if(this.included[domain][ref['id']]) {

				const propData = this.included[domain][ref['id']];

				if(type === null || propData['attributes'][prefix + 'type'] === type) {
					result.push(propData['attributes'][prefix + 'value']);
				}
			}
		}

		return result;
	}


	/**
	 * Returns the property items matching the given type or types
	 *
	 * @param {array|string|null} type Property type name of list of type names, null for all property types
	 * @returns List of items with property data
	 */
	getPropertyItems(type = null) {

		let result = [];
		const prefix = super.type + '.property.';
		const domain = super.type + '/property';

		for(let ref of (this.relations[domain] && this.relations[domain]['data'] || [])) {

			if(this.included[domain] && this.included[domain][ref['id']]) {

				const propData = this.included[domain] && this.included[domain][ref['id']];

				if(type === null || type === propData['attributes'][prefix + 'type']
					|| Array.isArray(type) && type.includes(propData['attributes'][prefix + 'type'])
				) {
					result.push(new Item(propData));
				}
			}
		}

		return result;
	}


	/**
	 * Returns the referenced items
	 *
	 * @param {string} domain Domain name like "text", "media", "price", etc.
	 * @param {array|string|null} type Type name or names, null for all types
	 * @param {array|string|null} listtype List type name or names, null for all types
	 * @returns List of referenced items from the given domain
	 */
	getRelItems(domain, type = null, listtype = null) {

		let result = [];
		const prefix = super.type + '.lists.';

		for(let ref of (this.relations[domain] && this.relations[domain]['data'] || [])) {

			const ltype = ref['attributes'] && ref['attributes'][prefix + 'type'];

			if((listtype === null || listtype === ltype || Array.isArray(listtype) && listtype.includes(ltype))
				&& this.included[domain] && this.included[domain][ref['id']]
			) {
				const itemData = this.included[domain][ref['id']];
				const refType = itemData['attributes'] && itemData['attributes'][itemData['type'] + '.type'];

				if(type === null || type === refType || type.includes(refType)) {

					itemData['attributes'] = Object.assign(itemData['attributes'] || {}, ref['attributes']);
					result.push(new RelItem(itemData, this.included));
				}
			}
		}

		return result;
	}
}
