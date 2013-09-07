# Purchases Module

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
$store_purchase->items(array('is_payable' = TRUE)); // return all the purchase items with flag "is_payable" set to TRUE as an array
$store_purchase->items(array('is_payable' = TRUE, 'product')); // return all the purchase items with flag "is_payable" set to TRUE and are of type 'product' as an array

```

All of these types of queries can be uesed by ``items_count()`` and ``total_price()``

```php

$store_purchase->items_count(array('product', 'shipping'));
$store_purchase->total_price(array('is_payable' = TRUE));

```

## Price Freezing

Normally all prices for purchase items are derieved dynamically, by calling ->price() and ->currency() methods on the reference object (be that a product, a promotion etc.), calculated with the current monetary exchange rates. Once you call the ``freeze()`` method on the purchase (and save it) both the exchange rates and the prices are set to the purchase disallowing any further modification of the purchase, even if the reference's price changes, the purchase item's prices will stay the same as in the moment of freezing.

```php

$purchase
	->freeze()
	->save();

```

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

## License

Copyright (c) 2012-2013, OpenBuildings Ltd. Developed by Ivan Kerin as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.

