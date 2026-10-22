<?php

/**
 * The structured scope of a moving / freight offer (SRS §7.4). `schema` is a JSON Schema (draft-07 subset: type, enum,
 * minimum, maximum, maxLength, maxItems, uniqueItems, required, properties, items, additionalProperties) checked by
 * `JsonSchemaValidator`; a change to it is a new `version` and never rewrites a stored scope (versions are append-only).
 */
return [
    'version' => 1,

    // Families whose offers must carry a scope (ServiceFamilyKind values).
    'required_families' => ['moving', 'freight'],

    'defaults' => [
        // What a customer's request implies when the form does not say.
        'tolls' => 'at_cost_with_evidence',
        'parking' => 'at_cost_with_evidence',
    ],

    'schema' => [
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['vehicle_class', 'helpers', 'packing', 'stairs', 'assembly', 'loading_equipment', 'waiting_allowance_minutes', 'extra_stops_included', 'tolls', 'parking'],
        'properties' => [
            'version' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 1000],
            'vehicle_class' => ['type' => 'string', 'maxLength' => 64],
            'helpers' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10],
            'packing' => ['type' => 'string', 'enum' => ['none', 'wrap_only', 'full_pack']],
            'stairs' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['handling'],
                'properties' => [
                    'handling' => ['type' => 'string', 'enum' => ['not_needed', 'included', 'extra_per_floor']],
                    'included_floors' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 40],
                ],
            ],
            'assembly' => ['type' => 'string', 'enum' => ['none', 'disassembly', 'assembly', 'both']],
            'loading_equipment' => [
                'type' => 'array',
                'maxItems' => 6,
                'uniqueItems' => true,
                'items' => ['type' => 'string', 'enum' => ['hand_trolley', 'forklift', 'crane', 'tail_lift', 'straps_blankets', 'pallet_jack']],
            ],
            'waiting_allowance_minutes' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 480],
            'extra_stops_included' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10],
            'tolls' => ['type' => 'string', 'enum' => ['included', 'at_cost_with_evidence', 'excluded']],
            'parking' => ['type' => 'string', 'enum' => ['included', 'at_cost_with_evidence', 'excluded']],
            'exclusions' => ['type' => 'string', 'maxLength' => 500],
        ],
    ],

    // Which scope items an adjustment type may refer to (BookingAdjustmentType value => ScopeItem values).
    'adjustment_items' => [
        'extra_helper' => ['helpers'],
        'stairs' => ['stairs'],
        'extra_stop' => ['extra_stops'],
        'waiting' => ['waiting_allowance'],
        'toll_parking' => ['tolls', 'parking'],
        'scope_change' => ['helpers', 'stairs', 'extra_stops', 'waiting_allowance', 'tolls', 'parking', 'packing', 'assembly', 'loading_equipment'],
    ],

    'attachments' => [
        'disk' => env('BOOKING_ATTACHMENT_DISK'),
        'photo_max_kilobytes' => (int) env('BOOKING_PHOTO_MAX_KB', 8192),
        'photo_max_edge' => 2000,
        'video_max_kilobytes' => (int) env('BOOKING_VIDEO_MAX_KB', 51200),
        'document_max_kilobytes' => 8192,
        'video_mimes' => ['video/mp4' => ['mp4', 'm4v'], 'video/quicktime' => ['mov'], 'video/webm' => ['webm']],
        // Per booking and kind, and the files a customer may hold unreferenced at once.
        'max_photos' => 12,
        'max_videos' => 2,
        'max_documents' => 5,
        'max_unattached' => 20,
        'per_minute' => (int) env('BOOKING_ATTACHMENT_PER_MINUTE', 20),
        'url_ttl_minutes' => 10,
    ],
];
