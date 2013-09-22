<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Jam_Behavior_Freezable extends Jam_Behavior {

	public $_associations;
	public $_fields;
	public $_parent;

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$this->_associations = (array) $this->_associations;
		$this->_fields = (array) $this->_fields;

		if ( ! $this->_parent) 
		{
			$meta->field('is_frozen', Jam::field('boolean'));
			$meta->field('is_just_frozen', Jam::field('boolean', array('in_db' => FALSE)));
		}
	}

	public function model_after_save(Jam_Model $model, Jam_Event_Data $data)
	{
		$model->is_just_frozen = FALSE;
	}

	public function model_after_check(Jam_Model $model, Jam_Event_Data $data)
	{
		if ($model->loaded() AND $model->is_frozen() AND ! $model->is_just_frozen()) 
		{
			foreach ($this->_associations as $name) 
			{
				if (count($model->{$name}) !== count($model->{$name}->original()))
				{
					$model->errors()->add($name, 'frozen');
				}
			}

			foreach ($this->_fields as $name) 
			{
				if ($model->changed($name))
				{
					$model->errors()->add($name, 'frozen');
				}
			}
		}
	}

	public function call_associations_method(Jam_Model $model, $method)
	{
		foreach ($this->_associations as $name) 
		{
			if ($model->meta()->association($name) instanceof Jam_Association_Collection ) 
			{
				foreach ($model->{$name}->as_array() as $item) 
				{
					$item->{$method}();
				}
			}
			else
			{
				$model->{$name}->{$method}();
			}
		}
	}

	public function model_call_freeze(Jam_Model $model, Jam_Event_Data $data)
	{
		$this->call_associations_method($model, 'freeze');

		foreach ($this->_fields as $name) 
		{
			$model->{$name} = $model->{$name}();
		}

		if ( ! $this->_parent)
		{
			$model->is_frozen = TRUE;
			$model->is_just_frozen = TRUE;
		}

		$data->return = $model;
	}

	public function model_call_unfreeze(Jam_Model $model, Jam_Event_Data $data)
	{
		$this->call_associations_method($model, 'unfreeze');

		foreach ($this->_fields as $name) 
		{
			$model->{$name} = NULL;
		}

		if ( ! $this->_parent) 
		{
			$model->is_frozen = FALSE;
			$model->is_just_frozen = FALSE;
		}

		$data->return = $model;
	}

	public function model_call_is_frozen(Jam_Model $model, Jam_Event_Data $data)
	{
		if ($this->_parent) 
		{
			$data->return = $model->get_insist($this->_parent)->is_frozen();
		}
		else
		{
			$data->return = $model->is_frozen;
		}
	}

	public function model_call_is_just_frozen(Jam_Model $model, Jam_Event_Data $data)
	{
		if ($this->_parent) 
		{
			$data->return = $model->get_insist($this->_parent)->is_just_frozen();
		}
		else
		{
			$data->return = $model->is_just_frozen;
		}
	}

}
