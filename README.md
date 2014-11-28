# Purchases Module

[![Build Status](https://travis-ci.org/OpenBuildings/purchases.png?branch=master)](https://travis-ci.org/OpenBuildings/purchases)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/OpenBuildings/purchases/badges/quality-score.png?s=e9d8fb56ba6287ac409e35a485445071ad52eebe)](https://scrutinizer-ci.com/g/OpenBuildings/purchases/)
[![Code Coverage](https://scrutinizer-ci.com/g/OpenBuildings/purchases/badges/coverage.png?s=80b3817f7aa1d6b14e56a45ba054f44cb4df695b)](https://scrutinizer-ci.com/g/OpenBuildings/purchases/)
[![Latest Stable Version](https://poser.pugx.org/openbuildings/purchases/v/stable.png)](https://packagist.org/packages/openbuildings/purchases)


This is a Kohana module that gives you out of the box functionality for multi-brand purchases (each purchase may have Items from different sellers, each handling their portion of products independently)

It has support for eMerchantPay and Paypal at the moment

## Instalation

All the purchase models work out of the box. However to use it properly you need to configure the models you want to sell by implementing Sellable interface. E.g

```php
class Model_Product extends Jam_Model implements Sellable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'price' => Jam::field('price'),
			));
	}

	public function price_for_purchase_item(Model_Purchase_Item $item)
	{
		return $this->price;
	}

	public function currency()
	{
		return $this->currency;
	}
}
```

You need to add the "Buyer" behavior for you user model. It adds ``current_purchase`` and ``purchases`` associations:

```php
class Model_User extends Kohana_Model_User {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'buyer' => Jam::association('buyer'),
			));
		// ...
	}
}
```

## Purchase, Brand Purchase and Purchase Items

The basic structure of the purchase is one purchase, representing the user's view of what is happening, that has several Brand_Purchase objects, one for each brand, and each one of these has a "Purchase_Items" assigned to it.

## Prices

This module heavily utilizes jam-monetary - all of its price related methods and fields are Jam_Price objects allowing you to safely conduct price calculations, so that currency conversions happen transparently. For more informations see: [https://github.com/OpenBuildings/jam-monetary](openbuildings/jam-monetary)

__Purchase_Item flags__

Purchase item has important "flags"

- __is_payable__ - that means that this is an item that should be "payed for" by the buyer, and will be added to his total bill. This is needed as some items need to be visible (and calculated) only for the seller for example, but should not appear on the buyer's bill.
- __is_discount__ - this means that the purchase item's price should be negative value. This enforces a validation - discounted items can only have negative prices, whereas normal ones must always have positive prices.

__Item Querying__
You can query items of the Brand Purchase with the ``items()`` method:

```php
$brand_purchase->items(); // return all the purchase items as an array
$brand_purchase->items('product'); // return all the purchase items with model "purchase_item_product" as an array
$brand_purchase->items(array('product', 'shipping')); // return all the purchase items with model "purchase_item_product" or "purchase_item_shipping" as an array
$brand_purchase->items(array('is_payable' => TRUE)); // return all the purchase items with flag "is_payable" set to TRUE as an array
$brand_purchase->items(array('is_payable' => TRUE, 'product')); // return all the purchase items with flag "is_payable" set to TRUE and are with model "purchase_item_product" as an array
$brand_purchase->items(array('not' => 'shipping')); // return all the purchase items that are not instance of model "purchase_item_shipping"
```

All of these types of queries can be used by ``items_count()`` and ``total_price()``

There is also "items_quantity" which sums the quantities of all the items, matched by the filters.

```php
$brand_purchase->items_count(array('product', 'shipping'));
$brand_purchase->total_price(array('is_payable' = TRUE));
$brand_purchase->items_quantity(array('is_payable' = TRUE));
```

All of these methods can also be executed on Model_Purchase objects, giving you an aggregate of all the brand_purchases. For example:

```php
// This will return the quantity of all the payable items in all the brand_purchases of this purchase.
$purchase->items_quantity(array('is_payable' => TRUE));
```

There is a special method that is available only on the Model_Brand_Purchase object. ``total_price_ratio`` - it will return what part of the whole purchase is the particular brand purchase (from 0 to 1). You can pass filters to it too so only certain purchase_items will be taken into account.

```php
$brand_purchase->total_price_ratio(array('is_payable' => TRUE)); // Will return e.g. 0.6
```

## Price Freezing

Normally all prices for purchase items are derived dynamically, by calling ->price_for_purchase_item() method on the reference object (be that a product, a promotion etc.), calculated with the current monetary exchange rates. Once you call the ``freeze()`` method on the purchase (and save it) both the exchange rates and the prices are set to the purchase disallowing any further modification of the purchase, even if the reference's price changes, the purchase item's prices will stay the same as in the moment of freezing.

```php
$purchase
	->freeze()
	->save();
```

If you want to modify the purchase after that, you'll have to ``unfreeze()`` it. Also if you want to know the state of the purchase, there is ``isFrozen`` flag.
```php
$purchase->unfreeze();
$purchase->isFrozen();
```

Once the purchase has been frozen and saved, any change to the frozen fields / associations will be treated as validation errors.

## Freezable traits

In order for that to work across all the models of the purchase, the [`clippings/freezable`](https://github.com/clippings/freezable) package is used. It has useful traits for keeping some values or collections frozen.

```php
class Model_Purchase extends Jam_Model {

	use Clippings\Freezable\FreezableCollectionTrait {
		performFreeze as freezeCollection;
		performUnfreeze as unfreezeCollection;
	};

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'brand_purchases' => Jam::association('has_many'),
			))
			->fields(array(
				'is_frozen' => Jam::field('boolean'),
				'price' => Jam::field('serializable'),
			));
	}

	public function price()
	{
		return $this->isFrozen() ? $this->price : $this->computePrice();
	}

	public function isFrozen()
	{
		return $this->is_frozen;
	}

	public function setFrozen($frozen)
	{
		$this->is_frozen = (bool) $frozen;

		return $this;
	}

	public function performFreeze()
	{
		$this->freezeCollection();

		$this->price = $this->price();
	}

	public function performUnfreeze()
	{
		$this->unfreezeCollection();

		$this->price = NULL;
	}

	public function getItems()
	{
		return $this->books;
	}
	//...
}
```

That means that whenever the model is "frozen" then the field named "price" will be assigned the value of the method "price()".
And all the associations will be also "frozen". The associations themselves have to be Freezable (implement the `FreezableInterface`) in order for this to work. And the price() method, as well as any other fields, have to take into account if the object is frozen. E.g.

```
public function price()
{
	return $this->isFrozen() ? $this->price : $this->compute_price();
}
```

## Adding / Updating single items

You can add an item to the purchase with the ``add_item()`` method. It would search all the purchase items in all the brand_items, If the same item is found elsewhere it would update its quantity, otherwise it would add it to the appropriate brand_item (or create that if none exist):

```

$purchse
	->add_item($brand, $new_purchase_item);

```

## EMP Processor

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
					->execute($form->as_array());

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

## EMP VBV Processor

This uses EMP credit card processor but with VBV/3DSecure utalizing authorize and execute methods

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
				->build('payment', array('model' => 'payment_paypal_vbv'))
					->authorize($form->vbv_params('/payment/complete'));

			// We need to save the form somewhere as it is later used for execute method
			$this->session->set('emp_form', $form->as_array());

			$this->redirect($purchase->payment->authorize_url());
		}

		$this->template->content = View::factory('payment/index', array('form' => Jam::form($form)));
	}

	public function action_complete()
	{
		$purchase = // Load purchase from somewhere

		if ( ! $purchase->is_paid())
		{
			$form = Jam::build('emp_form', array($this->session->get_once('emp_form')));

			$purchase
				->payment
					->execute($form->as_array());
		}

		$this->template->content = View::factory('payment/complete', array('purchase' => $purchase));
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

## PayPal Processor

A PayPal transaction requires 3 steps - creating the transaction, authorizing it by the user through PayPal's interface, and executing the transaction onces its been authorized.

``$processor->next_url()`` is used to go to the PayPal authorization page.

```php
class Controller_Payment extends Controller_Template {

	public function action_index()
	{
		$purchase = // Load purchase from somewhere

		if ($this->request->method() === Request::POST AND $form->check())
		{
			$purchase
				->build('payment', array('model' => 'payment_paypal'))
					->authorize(array('success_url' => '/payment/complete', 'cancel_url' => '/payment/canceled'));

			$this->redirect($purchase->payment->authorize_url());
		}

		$this->template->content = View::factory('payment/index');
	}

	public function action_complete()
	{
		$purchase = // Load purchase from somewhere

		$purchase
			->payment
				->execute(array('payer_id' => Request::initial()->query('PayerID')));

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

Refunds are performed with special Model_Brand_Refund objects - each refund is specific to a brand purchase - if you do not set any custom items, then all of them will be refunded (the whole transaction) otherwise, you can add Model_Brand_Refund_Item objects for refunding specific items (partial refund).

```php
$brand_purchase = // Load brand purchase

$refund = $brand_purchase->refunds->create(array(
	'items' => array(
		// The whole price of a specific item
		array('purchase_item' => $brand_purchase->items[0])

		// Parital amount of an item
		array('purchase_item' => $brand_purchase->items[1], 'amount' => 100)
	)
));

$refund
	->execute();
```

Later you can retrieve the refunds from the brand purchase or issue multiple refunds

## Extending payment

``execute()``, ``authorize()`` and ``refund()`` methods on the payment model, trigger some events and save the model - respectively:

 * model.before_execute
 * model.after_execute
 * model.before_authorize
 * model.after_authorize
 * model.before_refund
 * model.after_refund

Before methods are executed before any payment operations. After - after the payment operation is complete (money is sent) and the models are saved. Here's how you can use it:

```php
public function initialize(Jam_Meta $meta, $name)
{
	parent::initialize($meta, $name);

	$meta
		->events()
			->bind('model.before_execute', array($this, 'change_status'))
			->bind('model.before_execute', array($this, 'add_fees'))
			->bind('model.after_execute', array($this, 'send_user_emails'));
}

public static function change_status(Model_Payment $payment, Jam_Event_Data $data)
{
	foreach ($payment->get_insist('purchase')->brand_purchases as $brand_purchase)
	{
		$brand_purchase->status = Model_Brand_Purchase::PAID;
	}

	$payment->purchase = $payment->purchase;
}
//...
```

Refund events also send the Model_Brand_Refund object as the third argument.

## Updating Items and Extending Updates

Both ``brand_purchase`` and ``purchase`` have an update_items method, which trigger the brand_purchase's event 'model.update_items'. This is used mainly by external modules that can hook into purchases and add / update purchase_items when that event is triggered. For example openbuildings/shipping module uses that to add / update shipping items.

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

	public function update_items(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		if ( ! $brand_purchase->items('shipping'))
		{
			$brand_purchase->items []= Jam::build('purchase_item_shipping', array(
				'reference' => $brand_purchase->shipping // some shipping object
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

	public function filter_shipping_items(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data, array $items, array $filter)
	{
		$items = is_array($data->return) ? $data->return : $items;
		$filtered = array();

		foreach ($items as $item)
		{
			if (array_key_exists('shippable', $filter) AND ($item->reference instanceof Shippable) !== $filter['shippable'])
			{
				continue;
			}

			$filtered []= $item;
		}

		$data->return = $filtered;
	}
}
```

## Running tests

Tests should be run with selenium running (on port 4444 local)

E.g.

	xvfb-run java -jar vendor/claylo/selenium-server-standalone/selenium-server-standalone-2.*.jar

## License

Copyright (c) 2012-2013, OpenBuildings Ltd. Developed by Ivan Kerin as part of [clippings.com](http://clippings.com)

Under BSD-3-Clause license, read LICENSE file.

