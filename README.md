# Purchases Module

This is a kohana module that gives you out of the box functionality for multistore purchases (each purchase may have Items from different sellers, each handling their portion of products independantly)

It has support for eMerchantPay and Paypal at the moment

## Purchase, Store Purchase and Purchase Items

The basic structure of the purchase is one purchase, representing the user's view of what is happening, that has several Store_Purchase objects, one for each store, and each one of these has a "Purchase_Items" assigned to it.

__Purchase_Item flags__

Purchase item has important "flags" 

- is_payable - that means that this is an item that should be "payed for" by the buyer, and will be added to his total bill. This is needed as some items need to be visible (and calculated) only for the seller for example, but should not appear on the buyer's bill. 
- is_discount - this means that the purchase item's price should be negative value. This enforses a validation - discounted items can only have negative prices, whereas normal ones must always have positive prices.

__Item Querying__
You can query items of the Store Purchase with the ``items()`` method:

```php

$store_purchase->items(); // return all the purchase items as an array
$store_purchase->items('product'); // return all the purchase items with type "product" as an array
$store_purchase->items(array('product', 'shipping'); // return all the purchase items with type "product" or 'shipping' as an array
$store_purchase->items(array('is_payable' = TRUE); // return all the purchase items with flag "is_payable" set to TRUE as an array
$store_purchase->items(array('is_payable' = TRUE, 'product'); // return all the purchase items with flag "is_payable" set to TRUE and are of type 'product' as an array

```

All of these types of queries can be uesed by ``items_count()`` and ``total_price()``

## Price Freezing

Normally all prices for purchase items are derieved dynamically, by calling ->price() and ->currency() methods on the reference object (be that a product, a promotion etc.), calculated with the current monetary exchange rates. Once you call the ``freeze()`` method on the purchase (and save it) both the exchange rates and the prices are set to the purchase disallowing any further modification of the purchase, even if the reference's price changes, the purchase item's prices will stay the same as in the moment of freezing.

```php

$purchase
	->freeze()
	->save();

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
			$processor = new Processor_Emp($form->as_array());
			$purchase
				->pay($processor)
				->save();

			if ($purchase->payment->status == Model_Payment::PAID)
			{
				$this->redirect('/checkout/complete');
			}
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



## License

Copyright (c) 2012-2013, OpenBuildings Ltd. Developed by Ivan Kerin as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.

