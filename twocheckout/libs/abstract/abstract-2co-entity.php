<?php

/**
 * Class Verifone_Api_Entity
 */
abstract class Twocheckout_Entity implements Arrayable2CO, Jsonable2CO
{
	protected $data;

	/**
	 * @inheritDoc
	 */
	abstract public function toJson();

	/**
	 * @inheritDoc
	 */
	abstract public function toArray();
}
