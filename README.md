# Purchases Module

[![Build Status](https://travis-ci.org/OpenBuildings/purchases.png?branch=master)](https://travis-ci.org/OpenBuildings/purchases)
[![Coverage Status](https://coveralls.io/repos/OpenBuildings/purchases/badge.png?branch=master)](https://coveralls.io/r/OpenBuildings/purchases?branch=master)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/purchases/v/stable.png)](https://packagist.org/packages/openbuildings/purchases)


This is a kohana module that gives you out of the box functionality for multistore purchases (each purchase may have Items from different sellers, each handling their portion of products independantly)

It has support for eMerchantPay and Paypal at the moment

## Purchase, Store Purchase and Purchase Items

The basic structure of the purchase is one purchase, representing the user's view of what is happening, that has several Store_Purchase objects, one for each store, and each one of these has a "Purchase_Items" assigned to it.

## Prices

This module heavily utalizes jam-monetary - all of its price related methods and fields are Jam_Price objects allowing you to safly conduct price calculations, so that currency conversions happen transparently. For more informations see: [https://github.com/OpenBuildings/jam-monetary](openbuildings/jam-monetary)

__Purchase_Item flags__

Purchase item has important "flags" 

- __is_payable__ - that means that this is an item that should be "payed for" by the buyer, and will be added to his total bill. This is needed as some items need to be visible (and calculated) only for the seller for example, but should not appear on the buyer's bill. 
- __is_discount__ - this means that the purchase item's price should be negative value. This enforses a validation - discounted items can only have negative prices, whereas normal ones must always have positive prices.

__Item Querying__
You can query items of the Store Purchase with the ``items()`` method:

```php

$store_purchase->items(); // return all the purchase items as an array
$store_purchase->items('product'); // return all the purchase items with type "product" as an array
$store_purchase->items(array('product', 'shipping')); // return all the purchase items with type "product" or 'shipping' as an array
$store_purchase->items(array('is_payable' => TRUE)); // return all the purchase items with flag "is_payable" set to TRUE as an array
$store_purchase->items(array('is_payable' => TRUE, 'product')); // return all the purchase items with flag "is_payable" set to TRUE and are of type 'product' as an array

```

All of these types of queries can be uesed by ``items_count()`` and ``total_price()``

```php

$store_purchase->items_count(array('product', 'shipping'));
$store_purchase->total_price(array('is_payable' = TRUE));

```

## Price Freezing

Normally all prices for purchase items are derieved dynamically, by calling ->price_for_purchase_item() method on the reference object (be that a product, a promotion etc.), calculated with the current monetary exchange rates. Once you call the ``freeze()`` method on the purchase (and save it) both the exchange rates and the prices are set to the purchase disallowing any further modification of the purchase, even if the reference's price changes, the purchase item's prices will stay the same as in the moment of freezing.

```php

$purchase
	->freeze()
	->save();

```

If you want to modify the purchase after that, you'll have to ``unfreeze()`` it. Also if you want to know the state of the purchase, there is ``is_frozen`` flag.
```php
$purchase->unfreeze();
$purchase->is_frozen();
```

Once the purchase has been frozen, any change to the frozen fields / associations will be treated as validation errors.

## Adding / Updating single items

You can add an item to the purchase with the ``add_item()`` method. It would search all the purchase items in all the store_items, If the same item is found elsewhere it would update its quantity, otherwise it would add it to the appropriate store_item (or create that if none exist):

```

$purchse
	->add_item($store, $new_purchase_item);

```

## EMP Processors

To Use the emp processor you'll need a form on your page (you can use the included Model_Emp_Form). To supply the processor with the cc data.

in the controller:

```php

class Controller_Payment extends Controller_Template {

	public function action_index()
	{
		$purchase = // Load purchase from somewhere

		$form = Jam::build('emp_form', array($this->post()));
		if ($this->request->method() === Request::POST AND $form->check())
		{
			$purchase
				->build('payment', array('model' => 'payment_emp'))
					->execute($form->as_array())
					->save();

			$this->redirect('payment/complete');
		}

		$this->template->content = View::factory('payment/index', array('form' => Jam::form($form)))
	}
}
```
The form is a simple Jam_Form inside your view:

```php
<form action='payment/index'>
	<?php echo $form->row('input', 'card_holder_name') ?>
	<?php echo $form->row('input', 'card_number') ?>
	<?php echo $form->row('input', 'exp_month') ?>
	<?php echo $form->row('input', 'exp_year') ?>
	<?php echo $form->row('input', 'cvv') ?>
	<button type="submit">Process payment</button>
</form>
```

## Paypal Processor

A paypal transaction requires 3 steps - creating the transaction, authorizing it by the user through paypal's interface, and executing the transaction onces its been authorized.

``$processor->next_url()`` is used to go to the paypal authorization page.

```php
class Controller_Payment extends Controller_Template {

	public function action_index()
	{
		$purchase = // Load purchase from somewhere

		$form = Jam::build('emp_form', array($this->post()));
		if ($this->request->method() === Request::POST AND $form->check())
		{
			$purchase
				->build('payment', array('model' => 'payment_paypal'))
					->authorize(array('success_url' => '/payment/complete', 'cancel_url' => '/payment/canceled'))
					->save();

			$this->redirect($purchase->payment->authorize_url());
		}

		$this->template->content = View::factory('payment/index', array('form' => Jam::form($form)));
	}

	public function action_complete()
	{
		$purchase = // Load purchase from somewhere

		$purchase
			->payment
				->execute(array('payer_id' => Request::initial()->query('PayerID'))
				->save();

		$this->template->content = View::factory('payment/complete', array('purchase' => $purchase));
	}
}
```

## Additional billing info

You could pass additional info to the payment processor as billing address / name, by using the billing association.

```php
$purchase = Jam::find('purchase', 1);

$purchase->billing_address = Jam::build('address', array(
	'email' => 'john.smith@example.com',
	'first_name' => 'John',
	'last_name' => 'Smith',
	'line1' => 'Street 1',
	'city' => Jam::find('location', 'London'),
	'country' => Jam::find('location', 'United Kingdom'),
	'zip' => 'QSZND',
	'phone' => '1234567',
));
```

## Refunds

Refunds are performed with special Model_Store_Refund objects - each refund is specific to a store purchase - if you do not set any custom items, then all of them will be refunded (the whole transaction) otherwise, you can add Model_Store_Refund_Item objects for refunding specific items (partial refund).

```php

$store_purchase = // Load store purchase

$refund = $store_purchase->refunds->create(array(
	'items' => array(
		// The whole price of a specific item
		array('purchase_item' => $store_purchase->items[0])

		// Parital amount of an item
		array('purchase_item' => $store_purchase->items[1], 'amount' => 100)
	)
));

$refund
	->execute()
	->save();
```

Later you can retrieve the refunds from the store purchase or issue multiple refunds


## Updating Items and Extending Updates

Both ``store_purchase`` and ``purchase`` have an update_items method, wich trigger the store_purchase's event 'model.update_items'. This is used mainly by external modules that can hook into purchases and add / update purchase_items when that event is triggered. For example openbuildings/shipping module uses that to add / update shipping items.

For example we might have a behavior like this:

```php
class Jam_Behavior_MyBehavios extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->events()
				->bind('model.update_items', array($this, 'update_items'));
	}

	public function update_items(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		if ( ! $store_purchase->items('shipping'))
		{
			$store_purchase->items->build(array(
				'type' => 'shipping', 
				'reference' => $store_purchase->shipping // some shipping object
			));
		}
	}
}
```

## Extending filters

``items()``, ``items_count()`` and ``total_price()`` use filters array as an argument. It has a special event model.filter_items wich you can use in your behaviors to add additional filters or extend existing ones.

Here's an example of how you can do that:

```php
class Jam_Behavior_MyBehavios extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->events()
				->bind('model.filter_items', array($this, 'filter_items'));
	}

	public function filter_shipping_items(Model_Store_Purchase $store_purchase, Jam_Event_Data $data, array $items, array $filter)
		{
			$items = is_array($data->return) ? $data->return : $items;
			$filtered = array();

			foreach ($items as $item)
			{
				if (array_key_exists('shippable', $filter) AND ($item->reference instanceof Shippable) !== $filter['shippable'])
				{
					continue;
				}

				$filtered [] = $item;
			}

			$data->return = $filtered;
		}
}
```

## License

Copyright (c) 2012-2013, OpenBuildings Ltd. Developed by Ivan Kerin as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.

