<?php

namespace Api\Singleton;

interface Singleton
{
	/**
	 * @return mixed
	 */
	public static function getInstance(): mixed;
}