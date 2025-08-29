<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexstage_invitation_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name' => 'Event Date/Time',
                'key' => '{date_time}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-event-invitation',
                ],
            ],
            [
                'name' => 'Event Venue',
                'key' => '{venue}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-event-invitation',
                ],
            ],
            [
                'name' => 'Event Name',
                'key' => '{event_name}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-event-invitation',
                ],
            ],
            [
                'name' => 'Event Link',
                'key' => '{event_link}',
                'available' => [
                ],
                'templates' => [
                    'flexstage-event-invitation',
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
        $fields['{event_link}'] = $data['event_link'];
        
        return $fields;
    }
}