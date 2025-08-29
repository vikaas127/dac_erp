<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexstage_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name' => 'Ticket Details',
                'key' => '{ticket_details}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-tickets-success',
                ],
            ],
            [
                'name' => 'Event Date/Time',
                'key' => '{date_time}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-tickets-success',
                ],
            ],
            [
                'name' => 'Event Venue',
                'key' => '{venue}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-tickets-success',
                ],
            ],
            [
                'name' => 'Event Name',
                'key' => '{event_name}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-tickets-success',
                ],
            ],
        ];
    }

    /**
     * Flexibackup event merge fields
     */
    public function format($data)
    {
        $fields['{event_name}'] = $data['event_name'];
        $fields['{date_time}'] = $data['event_date_time'];
        $fields['{venue}'] = $data['event_venue'];
        $fields['{ticket_details}'] = $data['ticket_details'];
        
        return $fields;
    }
}