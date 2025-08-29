<?php

/**
 * Get flexstage menus
 * @param array $event
 * @return array
 */
function fs_event_client_menus(array $view_data): array
{
    $event = $view_data['event'];
    //this menu will be based on
    $menus =  [
        'home' => [
            'name' => _l('flexstage_home'),
            'href' => fs_get_event_url($event,''),
            'icon' => 'fa fa-home',
        ]
    ];
    //if we have tickets
    if($view_data['tickets']){
        $menus['tickets'] = [
            'name' => _l('flexstage_tickets'),
            'href' => fs_get_event_url($event,'tickets'),
            'icon' => 'fa fa-ticket',
        ];
    }
    return $menus;
}

/**
 * Determine if a user can access an event
 *
 * @param array $event
 * @return boolean
 */
function fs_user_can_not_access_event(array $event): bool
{
    return  (!$event['status'] && !is_staff_logged_in());
}

function fs_hide_by_event_privacy(string $privacy): bool{
    switch ($privacy) {
        case 'staff-only':
            return !is_staff_logged_in();
        case 'customer-only':
            return !is_client_logged_in();
        case 'customer-staff':
            return !is_logged_in();
        
        default:
            return false;
    }
}

/**
 * @param $event
 * @return string
 */
function fs_get_event_url(array $event, string $slug =""): string
{
    $url = flexstage_get_client_event_url($event['slug']);
    if($slug){
        $url .= '/'.$slug;
    }
    return $url;
}

function fs_get_event_tickets($event){
    if(!isset($event['id'])){
        return [];
    }
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticket_model');
    $sortings = [
        [
            'field' => 'price',
            'order' => 'ASC',
        ]
    ];
    $conditions = [
        'event_id' => $event['id'],
        'status' => 'open'
    ];

    return $CI->flexticket_model->all($conditions, $sortings);
}

function fs_get_event_ticket_sales_quantity($ticket_id){
    $CI = &get_instance();
    $CI->load->model('flexstage/flexticketsale_model');
    $conditions = [
        'ticketid' => $ticket_id,
    ];

    return $CI->flexticketsale_model->get_total_quantity($conditions);
}

function fs_get_event_speakers($event){
    if(!isset($event['id'])){
        return [];
    }
    $CI = &get_instance();
    $CI->load->model('flexstage/flexspeaker_model');
    $conditions = [
        'event_id' => $event['id'],
        'show' => 1,
    ];
    return $CI->flexspeaker_model->all($conditions);
}

function fs_get_event_videos($event){
    if(!isset($event['id'])){
        return [];
    }
    $CI = &get_instance();
    $CI->load->model('flexstage/flexvideo_model');
    $conditions = [
        'event_id' => $event['id'],
    ];
    return $CI->flexvideo_model->get($conditions);
}

function fs_get_event_images($event){
    if(!isset($event['id'])){
        return [];
    }
    $CI = &get_instance();
    $CI->load->model('flexstage/fleximage_model');
    $conditions = [
        'event_id' => $event['id'],
    ];
    return $CI->fleximage_model->all($conditions);
}

function fs_get_event($slug){
    $CI = &get_instance();
    $CI->load->model('flexstage/flexstage_model');
    return $CI->flexstage_model->get_event($slug);
}

/**
 * Get the price range for tickets
 *
 * @param array $tickets
 * @param $currency
 * @return string
 */
function fs_get_price_range(array $tickets, $currency)
{
    $tickets_count = count($tickets);

    if ($tickets_count > 0) {
        $cheapest_ticket = $tickets[0];
        $price_range = $currency->symbol . number_format($cheapest_ticket['price']);

        if ($tickets_count >= 2) {
            $most_expensive_ticket = $tickets[$tickets_count - 1];

            $price_range .= ' - ' . $currency->symbol . number_format($most_expensive_ticket['price']);
        }
    } else {
        $price_range = 'free';
    }

    return $price_range;
}

/**
 * Get video player URL
 *
 * @param string $url
 * @return string
 */
function fs_get_video_player_url($url)
{
    $youtube_string = 'https://www.youtube.com/';
    $vimeo_player = 'https://player.vimeo.com/';
    $video_parts = str_starts_with($url, $youtube_string)
        ? explode('=', $url)
        : explode('m/', $url);

    if (preg_match("/[a-z]/i", $video_parts[1])) {
        return $youtube_string . 'embed/' . $video_parts[1];
    }

    return $vimeo_player . 'video/' . $video_parts[1];
}

function fs_get_admin_event_details_url($event_id, $key = '', $ticket_id = ''){
    if(isset($event_id) && $event_id != ''){
        $url = 'flexstage/event_details/' . $event_id;

        if(isset($key) && $key != ''){
            $url .= '?key=' . $key;
        }
        
        if(isset($ticket_id) && $ticket_id != ''){
            $url .= '&ticket-id=' . $ticket_id;
        }

        return admin_url($url);
    }

    throw new Exception(_l('flexstage_event_id_empty'));
    
}

function fs_get_ticket_reference_code($length = 10)
{
    return strtoupper(bin2hex(random_bytes($length)));
}

function fs_get_event_socials($event){
    if(!isset($event['id'])){
        return [];
    }
    $CI = &get_instance();
    $CI->load->model('flexstage/flexsocialchannel_model');
    $conditions = [
        'event_id' => $event['id']
    ];

    return $CI->flexsocialchannel_model->all($conditions);
}