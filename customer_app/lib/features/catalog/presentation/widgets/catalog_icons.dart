import 'package:flutter/material.dart';

/// Maps the icon identifier of a family/category (set in the admin panel) to
/// a Material icon. Unknown identifiers fall back to a generic vehicle.
IconData catalogIcon(String? code) => switch (code) {
  'tuk_tuk' => Icons.electric_rickshaw,
  'taxi' => Icons.local_taxi,
  'car' => Icons.directions_car_filled_outlined,
  'motorcycle' => Icons.two_wheeler,
  'box' => Icons.inventory_2_outlined,
  'van' => Icons.airport_shuttle_outlined,
  'pickup' => Icons.local_shipping_outlined,
  'truck' => Icons.local_shipping,
  _ => Icons.directions_car_outlined,
};
