<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['flexstage/event/(:any)/tickets'] = 'flexevent/tickets/$1';
$route['flexstage/event/(:any)/success'] = 'flexevent/success/$1';
$route['flexstage/event/(:any)'] = 'flexevent/index/$1';
