<?php

return [
    'none' => 'None',
    'minutes' => ':minutes min',
    'label' => [
        'vehicle_class' => 'Vehicle class',
        'helpers' => 'Helpers',
        'packing' => 'Packing',
        'stairs' => 'Stairs and floors',
        'assembly' => 'Disassembly / assembly',
        'loading_equipment' => 'Loading equipment',
        'waiting_allowance' => 'Waiting allowance',
        'extra_stops' => 'Extra stops included',
        'tolls' => 'Tolls',
        'parking' => 'Parking',
        'exclusions' => 'Exclusions',
    ],
    'packing' => ['none' => 'No packing', 'wrap_only' => 'Wrapping only', 'full_pack' => 'Full packing'],
    'stairs' => ['not_needed' => 'Not needed', 'included' => 'Included up to :floors floor(s)', 'extra_per_floor' => 'Extra charge per floor (first :floors included)'],
    'assembly' => ['none' => 'None', 'disassembly' => 'Disassembly', 'assembly' => 'Assembly', 'both' => 'Disassembly and assembly'],
    'equipment' => ['hand_trolley' => 'Hand trolley', 'forklift' => 'Forklift', 'crane' => 'Crane', 'tail_lift' => 'Tail lift', 'straps_blankets' => 'Straps and blankets', 'pallet_jack' => 'Pallet jack'],
    'treatment' => ['included' => 'Included in the fare', 'at_cost_with_evidence' => 'At cost, with a receipt', 'excluded' => 'Not included'],
];
