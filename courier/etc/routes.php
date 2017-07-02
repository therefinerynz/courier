<?php
/**
 * Routes
 *
 * @link      	http://therefinery.co.nz
 * @copyright 	Copyright (c) 2017 The Refinery
 * @license 	https://github.com/therefinerynz/courier/blob/master/LICENSE.txt
 * @author    The Refinery
 * @package   Courier
 * @since     1.0.0
 */
return [
	'courier' => [ 'action' => 'courier/blueprints/index' ],
	'courier/blueprints' => [ 'action' => 'courier/blueprints/index' ],
	'courier/blueprints/new' => [ 'action' => 'courier/blueprints/create' ],
	'courier/blueprints/(?P<id>\d+)' => [ 'action' => 'courier/blueprints/edit' ],
	'courier/deliveries' => [ 'action' => 'courier/deliveries/index' ],
];
