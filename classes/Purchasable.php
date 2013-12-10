<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Implement me if you are anything like a purchase.
 *
 * E.g. Model_Purchase, Model_Store_Purchase or another sub-purchase.
 */
interface Purchasable {

	/**
	 * Get purchase_items.
	 * Accept filters.
	 *
	 * @param  array $types filters
	 * @return array of Model_Purchase_Item instances
	 */
	public function items($types = NULL);

	/**
	 * Return the total number of purchase items.
	 *
	 * @param  array $types filters
	 * @return integer
	 */
	public function items_count($types = NULL);

	/**
	 * Return the sum of the quantities for the purchase items
	 *
	 * @param  array $types filters
	 * @return integer
	 */
	public function items_quantity($types = NULL);

	/**
	 * Run update items on all of the children (store purchases, purchase items)
	 *
	 * @return Purchasable $this
	 */
	public function update_items();

	/**
	 * Replace the purchase items from a given type, removing old items
	 *
	 * @param  array $items array of new items
	 * @param  array $types filters
	 * @return Purchasable $this
	 */
	public function replace_items($items, $types = NULL);

	/**
	 * Return TRUE if there is a associated payment and its status is "paid"
	 *
	 * @return boolean
	 */
	public function is_paid();

	/**
	 * Return the date when the payment has been created
	 *
	 * @return string|NULL
	 */
	public function paid_at();

	/**
	 * Return the sum of all the prices from the purchase items
	 *
	 * @param  array $types filters
	 * @return Jam_Price
	 */
	public function total_price($types = NULL);

	/**
	 * Return the currency of all of the price fields in the purchase
	 *
	 * @return string
	 */
	public function currency();

	/**
	 * The currency used in "humanizing" any of the price fields in the purchase
	 *
	 * @return string
	 */
	public function display_currency();

	/**
	 * Return Monetary::instance() if not frozen.
	 * Freezable field.
	 *
	 * @return OpenBuildings\Monetary\Monetary
	 */
	public function monetary();
}
