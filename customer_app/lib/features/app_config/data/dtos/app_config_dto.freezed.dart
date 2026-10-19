// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint, type=warning, deprecated_member_use, deprecated_member_use_from_same_package
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'app_config_dto.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$AppConfigDto {

@JsonKey(name: 'default_locale') String? get defaultLocale; List<String> get locales; CountryDto? get country; CurrencyDto? get currency;@JsonKey(name: 'digits_preference') String? get digitsPreference; SupportDto? get support; bool get maintenance;/// Per-app version rules keyed by app: `customer`, `provider`.
 Map<String, AppVersionRuleDto> get apps;/// Current published policy version per policy type.
 Map<String, PolicyVersionDto> get policies; Map<String, bool> get features;
/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$AppConfigDtoCopyWith<AppConfigDto> get copyWith => _$AppConfigDtoCopyWithImpl<AppConfigDto>(this as AppConfigDto, _$identity);

  /// Serializes this AppConfigDto to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  final _this = this as AppConfigDto;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is AppConfigDto&&(identical(other.defaultLocale, _this.defaultLocale) || other.defaultLocale == _this.defaultLocale)&&const DeepCollectionEquality().equals(other.locales, _this.locales)&&(identical(other.country, _this.country) || other.country == _this.country)&&(identical(other.currency, _this.currency) || other.currency == _this.currency)&&(identical(other.digitsPreference, _this.digitsPreference) || other.digitsPreference == _this.digitsPreference)&&(identical(other.support, _this.support) || other.support == _this.support)&&(identical(other.maintenance, _this.maintenance) || other.maintenance == _this.maintenance)&&const DeepCollectionEquality().equals(other.apps, _this.apps)&&const DeepCollectionEquality().equals(other.policies, _this.policies)&&const DeepCollectionEquality().equals(other.features, _this.features));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as AppConfigDto;
  return Object.hash(runtimeType,_this.defaultLocale,const DeepCollectionEquality().hash(_this.locales),_this.country,_this.currency,_this.digitsPreference,_this.support,_this.maintenance,const DeepCollectionEquality().hash(_this.apps),const DeepCollectionEquality().hash(_this.policies),const DeepCollectionEquality().hash(_this.features));
}

@override
String toString() {
  final _this = this as AppConfigDto;
  return 'AppConfigDto(defaultLocale: ${_this.defaultLocale}, locales: ${_this.locales}, country: ${_this.country}, currency: ${_this.currency}, digitsPreference: ${_this.digitsPreference}, support: ${_this.support}, maintenance: ${_this.maintenance}, apps: ${_this.apps}, policies: ${_this.policies}, features: ${_this.features})';
}


}

/// @nodoc
abstract mixin class $AppConfigDtoCopyWith<$Res>  {
  factory $AppConfigDtoCopyWith(AppConfigDto value, $Res Function(AppConfigDto) _then) = _$AppConfigDtoCopyWithImpl;
@useResult
$Res call({
@JsonKey(name: 'default_locale') String? defaultLocale, List<String> locales, CountryDto? country, CurrencyDto? currency,@JsonKey(name: 'digits_preference') String? digitsPreference, SupportDto? support, bool maintenance, Map<String, AppVersionRuleDto> apps, Map<String, PolicyVersionDto> policies, Map<String, bool> features
});


$CountryDtoCopyWith<$Res>? get country;$CurrencyDtoCopyWith<$Res>? get currency;$SupportDtoCopyWith<$Res>? get support;

}
/// @nodoc
class _$AppConfigDtoCopyWithImpl<$Res>
    implements $AppConfigDtoCopyWith<$Res> {
  _$AppConfigDtoCopyWithImpl(this._self, this._then);

  final AppConfigDto _self;
  final $Res Function(AppConfigDto) _then;

/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? defaultLocale = freezed,Object? locales = null,Object? country = freezed,Object? currency = freezed,Object? digitsPreference = freezed,Object? support = freezed,Object? maintenance = null,Object? apps = null,Object? policies = null,Object? features = null,}) {
  return _then(AppConfigDto(
defaultLocale: freezed == defaultLocale ? _self.defaultLocale : defaultLocale // ignore: cast_nullable_to_non_nullable
as String?,locales: null == locales ? _self.locales : locales // ignore: cast_nullable_to_non_nullable
as List<String>,country: freezed == country ? _self.country : country // ignore: cast_nullable_to_non_nullable
as CountryDto?,currency: freezed == currency ? _self.currency : currency // ignore: cast_nullable_to_non_nullable
as CurrencyDto?,digitsPreference: freezed == digitsPreference ? _self.digitsPreference : digitsPreference // ignore: cast_nullable_to_non_nullable
as String?,support: freezed == support ? _self.support : support // ignore: cast_nullable_to_non_nullable
as SupportDto?,maintenance: null == maintenance ? _self.maintenance : maintenance // ignore: cast_nullable_to_non_nullable
as bool,apps: null == apps ? _self.apps : apps // ignore: cast_nullable_to_non_nullable
as Map<String, AppVersionRuleDto>,policies: null == policies ? _self.policies : policies // ignore: cast_nullable_to_non_nullable
as Map<String, PolicyVersionDto>,features: null == features ? _self.features : features // ignore: cast_nullable_to_non_nullable
as Map<String, bool>,
  ));
}
/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$CountryDtoCopyWith<$Res>? get country {
    if (_self.country == null) {
    return null;
  }

  return $CountryDtoCopyWith<$Res>(_self.country!, (value) {
    return _then(_self.copyWith(country: value));
  });
}/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$CurrencyDtoCopyWith<$Res>? get currency {
    if (_self.currency == null) {
    return null;
  }

  return $CurrencyDtoCopyWith<$Res>(_self.currency!, (value) {
    return _then(_self.copyWith(currency: value));
  });
}/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$SupportDtoCopyWith<$Res>? get support {
    if (_self.support == null) {
    return null;
  }

  return $SupportDtoCopyWith<$Res>(_self.support!, (value) {
    return _then(_self.copyWith(support: value));
  });
}
}


/// Adds pattern-matching-related methods to [AppConfigDto].
extension AppConfigDtoPatterns on AppConfigDto {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _AppConfigDto value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _AppConfigDto() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _AppConfigDto value)  $default,){
final _that = this;
switch (_that) {
case _AppConfigDto():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _AppConfigDto value)?  $default,){
final _that = this;
switch (_that) {
case _AppConfigDto() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function(@JsonKey(name: 'default_locale')  String? defaultLocale,  List<String> locales,  CountryDto? country,  CurrencyDto? currency, @JsonKey(name: 'digits_preference')  String? digitsPreference,  SupportDto? support,  bool maintenance,  Map<String, AppVersionRuleDto> apps,  Map<String, PolicyVersionDto> policies,  Map<String, bool> features)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _AppConfigDto() when $default != null:
return $default(_that.defaultLocale,_that.locales,_that.country,_that.currency,_that.digitsPreference,_that.support,_that.maintenance,_that.apps,_that.policies,_that.features);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function(@JsonKey(name: 'default_locale')  String? defaultLocale,  List<String> locales,  CountryDto? country,  CurrencyDto? currency, @JsonKey(name: 'digits_preference')  String? digitsPreference,  SupportDto? support,  bool maintenance,  Map<String, AppVersionRuleDto> apps,  Map<String, PolicyVersionDto> policies,  Map<String, bool> features)  $default,) {final _that = this;
switch (_that) {
case _AppConfigDto():
return $default(_that.defaultLocale,_that.locales,_that.country,_that.currency,_that.digitsPreference,_that.support,_that.maintenance,_that.apps,_that.policies,_that.features);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function(@JsonKey(name: 'default_locale')  String? defaultLocale,  List<String> locales,  CountryDto? country,  CurrencyDto? currency, @JsonKey(name: 'digits_preference')  String? digitsPreference,  SupportDto? support,  bool maintenance,  Map<String, AppVersionRuleDto> apps,  Map<String, PolicyVersionDto> policies,  Map<String, bool> features)?  $default,) {final _that = this;
switch (_that) {
case _AppConfigDto() when $default != null:
return $default(_that.defaultLocale,_that.locales,_that.country,_that.currency,_that.digitsPreference,_that.support,_that.maintenance,_that.apps,_that.policies,_that.features);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _AppConfigDto implements AppConfigDto {
  const _AppConfigDto({@JsonKey(name: 'default_locale') this.defaultLocale,  List<String> locales = const <String>[], this.country, this.currency, @JsonKey(name: 'digits_preference') this.digitsPreference, this.support, this.maintenance = false,  Map<String, AppVersionRuleDto> apps = const <String, AppVersionRuleDto>{},  Map<String, PolicyVersionDto> policies = const <String, PolicyVersionDto>{},  Map<String, bool> features = const <String, bool>{}}): _locales = locales,_apps = apps,_policies = policies,_features = features;
  factory _AppConfigDto.fromJson(Map<String, dynamic> json) => _$AppConfigDtoFromJson(json);

@override@JsonKey(name: 'default_locale') final  String? defaultLocale;
 final  List<String> _locales;
@override@JsonKey() List<String> get locales {
  if (_locales is EqualUnmodifiableListView) return _locales;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_locales);
}

@override final  CountryDto? country;
@override final  CurrencyDto? currency;
@override@JsonKey(name: 'digits_preference') final  String? digitsPreference;
@override final  SupportDto? support;
@override@JsonKey() final  bool maintenance;
/// Per-app version rules keyed by app: `customer`, `provider`.
 final  Map<String, AppVersionRuleDto> _apps;
/// Per-app version rules keyed by app: `customer`, `provider`.
@override@JsonKey() Map<String, AppVersionRuleDto> get apps {
  if (_apps is EqualUnmodifiableMapView) return _apps;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableMapView(_apps);
}

/// Current published policy version per policy type.
 final  Map<String, PolicyVersionDto> _policies;
/// Current published policy version per policy type.
@override@JsonKey() Map<String, PolicyVersionDto> get policies {
  if (_policies is EqualUnmodifiableMapView) return _policies;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableMapView(_policies);
}

 final  Map<String, bool> _features;
@override@JsonKey() Map<String, bool> get features {
  if (_features is EqualUnmodifiableMapView) return _features;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableMapView(_features);
}


/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$AppConfigDtoCopyWith<_AppConfigDto> get copyWith => __$AppConfigDtoCopyWithImpl<_AppConfigDto>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$AppConfigDtoToJson(this, );
}

@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _AppConfigDto&&(identical(other.defaultLocale, defaultLocale) || other.defaultLocale == defaultLocale)&&const DeepCollectionEquality().equals(other.locales, _locales)&&(identical(other.country, country) || other.country == country)&&(identical(other.currency, currency) || other.currency == currency)&&(identical(other.digitsPreference, digitsPreference) || other.digitsPreference == digitsPreference)&&(identical(other.support, support) || other.support == support)&&(identical(other.maintenance, maintenance) || other.maintenance == maintenance)&&const DeepCollectionEquality().equals(other.apps, _apps)&&const DeepCollectionEquality().equals(other.policies, _policies)&&const DeepCollectionEquality().equals(other.features, _features));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,defaultLocale,const DeepCollectionEquality().hash(_locales),country,currency,digitsPreference,support,maintenance,const DeepCollectionEquality().hash(_apps),const DeepCollectionEquality().hash(_policies),const DeepCollectionEquality().hash(_features));
}

@override
String toString() {
    return 'AppConfigDto(defaultLocale: $defaultLocale, locales: $locales, country: $country, currency: $currency, digitsPreference: $digitsPreference, support: $support, maintenance: $maintenance, apps: $apps, policies: $policies, features: $features)';
}


}

/// @nodoc
abstract mixin class _$AppConfigDtoCopyWith<$Res> implements $AppConfigDtoCopyWith<$Res> {
  factory _$AppConfigDtoCopyWith(_AppConfigDto value, $Res Function(_AppConfigDto) _then) = __$AppConfigDtoCopyWithImpl;
@override @useResult
$Res call({
@JsonKey(name: 'default_locale') String? defaultLocale, List<String> locales, CountryDto? country, CurrencyDto? currency,@JsonKey(name: 'digits_preference') String? digitsPreference, SupportDto? support, bool maintenance, Map<String, AppVersionRuleDto> apps, Map<String, PolicyVersionDto> policies, Map<String, bool> features
});


@override $CountryDtoCopyWith<$Res>? get country;@override $CurrencyDtoCopyWith<$Res>? get currency;@override $SupportDtoCopyWith<$Res>? get support;

}
/// @nodoc
class __$AppConfigDtoCopyWithImpl<$Res>
    implements _$AppConfigDtoCopyWith<$Res> {
  __$AppConfigDtoCopyWithImpl(this._self, this._then);

  final _AppConfigDto _self;
  final $Res Function(_AppConfigDto) _then;

/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? defaultLocale = freezed,Object? locales = null,Object? country = freezed,Object? currency = freezed,Object? digitsPreference = freezed,Object? support = freezed,Object? maintenance = null,Object? apps = null,Object? policies = null,Object? features = null,}) {
  return _then(_AppConfigDto(
defaultLocale: freezed == defaultLocale ? _self.defaultLocale : defaultLocale // ignore: cast_nullable_to_non_nullable
as String?,locales: null == locales ? _self._locales : locales // ignore: cast_nullable_to_non_nullable
as List<String>,country: freezed == country ? _self.country : country // ignore: cast_nullable_to_non_nullable
as CountryDto?,currency: freezed == currency ? _self.currency : currency // ignore: cast_nullable_to_non_nullable
as CurrencyDto?,digitsPreference: freezed == digitsPreference ? _self.digitsPreference : digitsPreference // ignore: cast_nullable_to_non_nullable
as String?,support: freezed == support ? _self.support : support // ignore: cast_nullable_to_non_nullable
as SupportDto?,maintenance: null == maintenance ? _self.maintenance : maintenance // ignore: cast_nullable_to_non_nullable
as bool,apps: null == apps ? _self._apps : apps // ignore: cast_nullable_to_non_nullable
as Map<String, AppVersionRuleDto>,policies: null == policies ? _self._policies : policies // ignore: cast_nullable_to_non_nullable
as Map<String, PolicyVersionDto>,features: null == features ? _self._features : features // ignore: cast_nullable_to_non_nullable
as Map<String, bool>,
  ));
}

/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$CountryDtoCopyWith<$Res>? get country {
    if (_self.country == null) {
    return null;
  }

  return $CountryDtoCopyWith<$Res>(_self.country!, (value) {
    return _then(_self.copyWith(country: value));
  });
}/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$CurrencyDtoCopyWith<$Res>? get currency {
    if (_self.currency == null) {
    return null;
  }

  return $CurrencyDtoCopyWith<$Res>(_self.currency!, (value) {
    return _then(_self.copyWith(currency: value));
  });
}/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$SupportDtoCopyWith<$Res>? get support {
    if (_self.support == null) {
    return null;
  }

  return $SupportDtoCopyWith<$Res>(_self.support!, (value) {
    return _then(_self.copyWith(support: value));
  });
}
}


/// @nodoc
mixin _$CountryDto {

 String get iso2;@JsonKey(name: 'phone_code') String? get phoneCode; String? get timezone;
/// Create a copy of CountryDto
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CountryDtoCopyWith<CountryDto> get copyWith => _$CountryDtoCopyWithImpl<CountryDto>(this as CountryDto, _$identity);

  /// Serializes this CountryDto to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  final _this = this as CountryDto;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CountryDto&&(identical(other.iso2, _this.iso2) || other.iso2 == _this.iso2)&&(identical(other.phoneCode, _this.phoneCode) || other.phoneCode == _this.phoneCode)&&(identical(other.timezone, _this.timezone) || other.timezone == _this.timezone));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as CountryDto;
  return Object.hash(runtimeType,_this.iso2,_this.phoneCode,_this.timezone);
}

@override
String toString() {
  final _this = this as CountryDto;
  return 'CountryDto(iso2: ${_this.iso2}, phoneCode: ${_this.phoneCode}, timezone: ${_this.timezone})';
}


}

/// @nodoc
abstract mixin class $CountryDtoCopyWith<$Res>  {
  factory $CountryDtoCopyWith(CountryDto value, $Res Function(CountryDto) _then) = _$CountryDtoCopyWithImpl;
@useResult
$Res call({
 String iso2,@JsonKey(name: 'phone_code') String? phoneCode, String? timezone
});




}
/// @nodoc
class _$CountryDtoCopyWithImpl<$Res>
    implements $CountryDtoCopyWith<$Res> {
  _$CountryDtoCopyWithImpl(this._self, this._then);

  final CountryDto _self;
  final $Res Function(CountryDto) _then;

/// Create a copy of CountryDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? iso2 = null,Object? phoneCode = freezed,Object? timezone = freezed,}) {
  return _then(CountryDto(
iso2: null == iso2 ? _self.iso2 : iso2 // ignore: cast_nullable_to_non_nullable
as String,phoneCode: freezed == phoneCode ? _self.phoneCode : phoneCode // ignore: cast_nullable_to_non_nullable
as String?,timezone: freezed == timezone ? _self.timezone : timezone // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [CountryDto].
extension CountryDtoPatterns on CountryDto {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _CountryDto value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _CountryDto() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _CountryDto value)  $default,){
final _that = this;
switch (_that) {
case _CountryDto():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _CountryDto value)?  $default,){
final _that = this;
switch (_that) {
case _CountryDto() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String iso2, @JsonKey(name: 'phone_code')  String? phoneCode,  String? timezone)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CountryDto() when $default != null:
return $default(_that.iso2,_that.phoneCode,_that.timezone);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String iso2, @JsonKey(name: 'phone_code')  String? phoneCode,  String? timezone)  $default,) {final _that = this;
switch (_that) {
case _CountryDto():
return $default(_that.iso2,_that.phoneCode,_that.timezone);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String iso2, @JsonKey(name: 'phone_code')  String? phoneCode,  String? timezone)?  $default,) {final _that = this;
switch (_that) {
case _CountryDto() when $default != null:
return $default(_that.iso2,_that.phoneCode,_that.timezone);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _CountryDto implements CountryDto {
  const _CountryDto({required this.iso2, @JsonKey(name: 'phone_code') this.phoneCode, this.timezone});
  factory _CountryDto.fromJson(Map<String, dynamic> json) => _$CountryDtoFromJson(json);

@override final  String iso2;
@override@JsonKey(name: 'phone_code') final  String? phoneCode;
@override final  String? timezone;

/// Create a copy of CountryDto
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CountryDtoCopyWith<_CountryDto> get copyWith => __$CountryDtoCopyWithImpl<_CountryDto>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$CountryDtoToJson(this, );
}

@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _CountryDto&&(identical(other.iso2, iso2) || other.iso2 == iso2)&&(identical(other.phoneCode, phoneCode) || other.phoneCode == phoneCode)&&(identical(other.timezone, timezone) || other.timezone == timezone));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,iso2,phoneCode,timezone);
}

@override
String toString() {
    return 'CountryDto(iso2: $iso2, phoneCode: $phoneCode, timezone: $timezone)';
}


}

/// @nodoc
abstract mixin class _$CountryDtoCopyWith<$Res> implements $CountryDtoCopyWith<$Res> {
  factory _$CountryDtoCopyWith(_CountryDto value, $Res Function(_CountryDto) _then) = __$CountryDtoCopyWithImpl;
@override @useResult
$Res call({
 String iso2,@JsonKey(name: 'phone_code') String? phoneCode, String? timezone
});




}
/// @nodoc
class __$CountryDtoCopyWithImpl<$Res>
    implements _$CountryDtoCopyWith<$Res> {
  __$CountryDtoCopyWithImpl(this._self, this._then);

  final _CountryDto _self;
  final $Res Function(_CountryDto) _then;

/// Create a copy of CountryDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? iso2 = null,Object? phoneCode = freezed,Object? timezone = freezed,}) {
  return _then(_CountryDto(
iso2: null == iso2 ? _self.iso2 : iso2 // ignore: cast_nullable_to_non_nullable
as String,phoneCode: freezed == phoneCode ? _self.phoneCode : phoneCode // ignore: cast_nullable_to_non_nullable
as String?,timezone: freezed == timezone ? _self.timezone : timezone // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}


/// @nodoc
mixin _$CurrencyDto {

 String get code; int get exponent;/// Symbols by language code (`ar`, `en`).
 Map<String, String> get symbol;
/// Create a copy of CurrencyDto
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CurrencyDtoCopyWith<CurrencyDto> get copyWith => _$CurrencyDtoCopyWithImpl<CurrencyDto>(this as CurrencyDto, _$identity);

  /// Serializes this CurrencyDto to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  final _this = this as CurrencyDto;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CurrencyDto&&(identical(other.code, _this.code) || other.code == _this.code)&&(identical(other.exponent, _this.exponent) || other.exponent == _this.exponent)&&const DeepCollectionEquality().equals(other.symbol, _this.symbol));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as CurrencyDto;
  return Object.hash(runtimeType,_this.code,_this.exponent,const DeepCollectionEquality().hash(_this.symbol));
}

@override
String toString() {
  final _this = this as CurrencyDto;
  return 'CurrencyDto(code: ${_this.code}, exponent: ${_this.exponent}, symbol: ${_this.symbol})';
}


}

/// @nodoc
abstract mixin class $CurrencyDtoCopyWith<$Res>  {
  factory $CurrencyDtoCopyWith(CurrencyDto value, $Res Function(CurrencyDto) _then) = _$CurrencyDtoCopyWithImpl;
@useResult
$Res call({
 String code, int exponent, Map<String, String> symbol
});




}
/// @nodoc
class _$CurrencyDtoCopyWithImpl<$Res>
    implements $CurrencyDtoCopyWith<$Res> {
  _$CurrencyDtoCopyWithImpl(this._self, this._then);

  final CurrencyDto _self;
  final $Res Function(CurrencyDto) _then;

/// Create a copy of CurrencyDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? code = null,Object? exponent = null,Object? symbol = null,}) {
  return _then(CurrencyDto(
code: null == code ? _self.code : code // ignore: cast_nullable_to_non_nullable
as String,exponent: null == exponent ? _self.exponent : exponent // ignore: cast_nullable_to_non_nullable
as int,symbol: null == symbol ? _self.symbol : symbol // ignore: cast_nullable_to_non_nullable
as Map<String, String>,
  ));
}

}


/// Adds pattern-matching-related methods to [CurrencyDto].
extension CurrencyDtoPatterns on CurrencyDto {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _CurrencyDto value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _CurrencyDto() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _CurrencyDto value)  $default,){
final _that = this;
switch (_that) {
case _CurrencyDto():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _CurrencyDto value)?  $default,){
final _that = this;
switch (_that) {
case _CurrencyDto() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String code,  int exponent,  Map<String, String> symbol)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CurrencyDto() when $default != null:
return $default(_that.code,_that.exponent,_that.symbol);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String code,  int exponent,  Map<String, String> symbol)  $default,) {final _that = this;
switch (_that) {
case _CurrencyDto():
return $default(_that.code,_that.exponent,_that.symbol);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String code,  int exponent,  Map<String, String> symbol)?  $default,) {final _that = this;
switch (_that) {
case _CurrencyDto() when $default != null:
return $default(_that.code,_that.exponent,_that.symbol);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _CurrencyDto implements CurrencyDto {
  const _CurrencyDto({required this.code, this.exponent = 2,  Map<String, String> symbol = const <String, String>{}}): _symbol = symbol;
  factory _CurrencyDto.fromJson(Map<String, dynamic> json) => _$CurrencyDtoFromJson(json);

@override final  String code;
@override@JsonKey() final  int exponent;
/// Symbols by language code (`ar`, `en`).
 final  Map<String, String> _symbol;
/// Symbols by language code (`ar`, `en`).
@override@JsonKey() Map<String, String> get symbol {
  if (_symbol is EqualUnmodifiableMapView) return _symbol;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableMapView(_symbol);
}


/// Create a copy of CurrencyDto
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CurrencyDtoCopyWith<_CurrencyDto> get copyWith => __$CurrencyDtoCopyWithImpl<_CurrencyDto>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$CurrencyDtoToJson(this, );
}

@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _CurrencyDto&&(identical(other.code, code) || other.code == code)&&(identical(other.exponent, exponent) || other.exponent == exponent)&&const DeepCollectionEquality().equals(other.symbol, _symbol));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,code,exponent,const DeepCollectionEquality().hash(_symbol));
}

@override
String toString() {
    return 'CurrencyDto(code: $code, exponent: $exponent, symbol: $symbol)';
}


}

/// @nodoc
abstract mixin class _$CurrencyDtoCopyWith<$Res> implements $CurrencyDtoCopyWith<$Res> {
  factory _$CurrencyDtoCopyWith(_CurrencyDto value, $Res Function(_CurrencyDto) _then) = __$CurrencyDtoCopyWithImpl;
@override @useResult
$Res call({
 String code, int exponent, Map<String, String> symbol
});




}
/// @nodoc
class __$CurrencyDtoCopyWithImpl<$Res>
    implements _$CurrencyDtoCopyWith<$Res> {
  __$CurrencyDtoCopyWithImpl(this._self, this._then);

  final _CurrencyDto _self;
  final $Res Function(_CurrencyDto) _then;

/// Create a copy of CurrencyDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? code = null,Object? exponent = null,Object? symbol = null,}) {
  return _then(_CurrencyDto(
code: null == code ? _self.code : code // ignore: cast_nullable_to_non_nullable
as String,exponent: null == exponent ? _self.exponent : exponent // ignore: cast_nullable_to_non_nullable
as int,symbol: null == symbol ? _self._symbol : symbol // ignore: cast_nullable_to_non_nullable
as Map<String, String>,
  ));
}


}


/// @nodoc
mixin _$SupportDto {

 String? get phone; String? get email; String? get whatsapp;
/// Create a copy of SupportDto
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$SupportDtoCopyWith<SupportDto> get copyWith => _$SupportDtoCopyWithImpl<SupportDto>(this as SupportDto, _$identity);

  /// Serializes this SupportDto to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  final _this = this as SupportDto;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is SupportDto&&(identical(other.phone, _this.phone) || other.phone == _this.phone)&&(identical(other.email, _this.email) || other.email == _this.email)&&(identical(other.whatsapp, _this.whatsapp) || other.whatsapp == _this.whatsapp));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as SupportDto;
  return Object.hash(runtimeType,_this.phone,_this.email,_this.whatsapp);
}

@override
String toString() {
  final _this = this as SupportDto;
  return 'SupportDto(phone: ${_this.phone}, email: ${_this.email}, whatsapp: ${_this.whatsapp})';
}


}

/// @nodoc
abstract mixin class $SupportDtoCopyWith<$Res>  {
  factory $SupportDtoCopyWith(SupportDto value, $Res Function(SupportDto) _then) = _$SupportDtoCopyWithImpl;
@useResult
$Res call({
 String? phone, String? email, String? whatsapp
});




}
/// @nodoc
class _$SupportDtoCopyWithImpl<$Res>
    implements $SupportDtoCopyWith<$Res> {
  _$SupportDtoCopyWithImpl(this._self, this._then);

  final SupportDto _self;
  final $Res Function(SupportDto) _then;

/// Create a copy of SupportDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? phone = freezed,Object? email = freezed,Object? whatsapp = freezed,}) {
  return _then(SupportDto(
phone: freezed == phone ? _self.phone : phone // ignore: cast_nullable_to_non_nullable
as String?,email: freezed == email ? _self.email : email // ignore: cast_nullable_to_non_nullable
as String?,whatsapp: freezed == whatsapp ? _self.whatsapp : whatsapp // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [SupportDto].
extension SupportDtoPatterns on SupportDto {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _SupportDto value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _SupportDto() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _SupportDto value)  $default,){
final _that = this;
switch (_that) {
case _SupportDto():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _SupportDto value)?  $default,){
final _that = this;
switch (_that) {
case _SupportDto() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String? phone,  String? email,  String? whatsapp)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _SupportDto() when $default != null:
return $default(_that.phone,_that.email,_that.whatsapp);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String? phone,  String? email,  String? whatsapp)  $default,) {final _that = this;
switch (_that) {
case _SupportDto():
return $default(_that.phone,_that.email,_that.whatsapp);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String? phone,  String? email,  String? whatsapp)?  $default,) {final _that = this;
switch (_that) {
case _SupportDto() when $default != null:
return $default(_that.phone,_that.email,_that.whatsapp);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _SupportDto implements SupportDto {
  const _SupportDto({this.phone, this.email, this.whatsapp});
  factory _SupportDto.fromJson(Map<String, dynamic> json) => _$SupportDtoFromJson(json);

@override final  String? phone;
@override final  String? email;
@override final  String? whatsapp;

/// Create a copy of SupportDto
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$SupportDtoCopyWith<_SupportDto> get copyWith => __$SupportDtoCopyWithImpl<_SupportDto>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$SupportDtoToJson(this, );
}

@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _SupportDto&&(identical(other.phone, phone) || other.phone == phone)&&(identical(other.email, email) || other.email == email)&&(identical(other.whatsapp, whatsapp) || other.whatsapp == whatsapp));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,phone,email,whatsapp);
}

@override
String toString() {
    return 'SupportDto(phone: $phone, email: $email, whatsapp: $whatsapp)';
}


}

/// @nodoc
abstract mixin class _$SupportDtoCopyWith<$Res> implements $SupportDtoCopyWith<$Res> {
  factory _$SupportDtoCopyWith(_SupportDto value, $Res Function(_SupportDto) _then) = __$SupportDtoCopyWithImpl;
@override @useResult
$Res call({
 String? phone, String? email, String? whatsapp
});




}
/// @nodoc
class __$SupportDtoCopyWithImpl<$Res>
    implements _$SupportDtoCopyWith<$Res> {
  __$SupportDtoCopyWithImpl(this._self, this._then);

  final _SupportDto _self;
  final $Res Function(_SupportDto) _then;

/// Create a copy of SupportDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? phone = freezed,Object? email = freezed,Object? whatsapp = freezed,}) {
  return _then(_SupportDto(
phone: freezed == phone ? _self.phone : phone // ignore: cast_nullable_to_non_nullable
as String?,email: freezed == email ? _self.email : email // ignore: cast_nullable_to_non_nullable
as String?,whatsapp: freezed == whatsapp ? _self.whatsapp : whatsapp // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}


/// @nodoc
mixin _$AppVersionRuleDto {

@JsonKey(name: 'min_version') String? get minVersion;@JsonKey(name: 'force_update') bool get forceUpdate;
/// Create a copy of AppVersionRuleDto
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$AppVersionRuleDtoCopyWith<AppVersionRuleDto> get copyWith => _$AppVersionRuleDtoCopyWithImpl<AppVersionRuleDto>(this as AppVersionRuleDto, _$identity);

  /// Serializes this AppVersionRuleDto to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  final _this = this as AppVersionRuleDto;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is AppVersionRuleDto&&(identical(other.minVersion, _this.minVersion) || other.minVersion == _this.minVersion)&&(identical(other.forceUpdate, _this.forceUpdate) || other.forceUpdate == _this.forceUpdate));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as AppVersionRuleDto;
  return Object.hash(runtimeType,_this.minVersion,_this.forceUpdate);
}

@override
String toString() {
  final _this = this as AppVersionRuleDto;
  return 'AppVersionRuleDto(minVersion: ${_this.minVersion}, forceUpdate: ${_this.forceUpdate})';
}


}

/// @nodoc
abstract mixin class $AppVersionRuleDtoCopyWith<$Res>  {
  factory $AppVersionRuleDtoCopyWith(AppVersionRuleDto value, $Res Function(AppVersionRuleDto) _then) = _$AppVersionRuleDtoCopyWithImpl;
@useResult
$Res call({
@JsonKey(name: 'min_version') String? minVersion,@JsonKey(name: 'force_update') bool forceUpdate
});




}
/// @nodoc
class _$AppVersionRuleDtoCopyWithImpl<$Res>
    implements $AppVersionRuleDtoCopyWith<$Res> {
  _$AppVersionRuleDtoCopyWithImpl(this._self, this._then);

  final AppVersionRuleDto _self;
  final $Res Function(AppVersionRuleDto) _then;

/// Create a copy of AppVersionRuleDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? minVersion = freezed,Object? forceUpdate = null,}) {
  return _then(AppVersionRuleDto(
minVersion: freezed == minVersion ? _self.minVersion : minVersion // ignore: cast_nullable_to_non_nullable
as String?,forceUpdate: null == forceUpdate ? _self.forceUpdate : forceUpdate // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [AppVersionRuleDto].
extension AppVersionRuleDtoPatterns on AppVersionRuleDto {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _AppVersionRuleDto value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _AppVersionRuleDto() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _AppVersionRuleDto value)  $default,){
final _that = this;
switch (_that) {
case _AppVersionRuleDto():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _AppVersionRuleDto value)?  $default,){
final _that = this;
switch (_that) {
case _AppVersionRuleDto() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function(@JsonKey(name: 'min_version')  String? minVersion, @JsonKey(name: 'force_update')  bool forceUpdate)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _AppVersionRuleDto() when $default != null:
return $default(_that.minVersion,_that.forceUpdate);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function(@JsonKey(name: 'min_version')  String? minVersion, @JsonKey(name: 'force_update')  bool forceUpdate)  $default,) {final _that = this;
switch (_that) {
case _AppVersionRuleDto():
return $default(_that.minVersion,_that.forceUpdate);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function(@JsonKey(name: 'min_version')  String? minVersion, @JsonKey(name: 'force_update')  bool forceUpdate)?  $default,) {final _that = this;
switch (_that) {
case _AppVersionRuleDto() when $default != null:
return $default(_that.minVersion,_that.forceUpdate);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _AppVersionRuleDto implements AppVersionRuleDto {
  const _AppVersionRuleDto({@JsonKey(name: 'min_version') this.minVersion, @JsonKey(name: 'force_update') this.forceUpdate = false});
  factory _AppVersionRuleDto.fromJson(Map<String, dynamic> json) => _$AppVersionRuleDtoFromJson(json);

@override@JsonKey(name: 'min_version') final  String? minVersion;
@override@JsonKey(name: 'force_update') final  bool forceUpdate;

/// Create a copy of AppVersionRuleDto
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$AppVersionRuleDtoCopyWith<_AppVersionRuleDto> get copyWith => __$AppVersionRuleDtoCopyWithImpl<_AppVersionRuleDto>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$AppVersionRuleDtoToJson(this, );
}

@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _AppVersionRuleDto&&(identical(other.minVersion, minVersion) || other.minVersion == minVersion)&&(identical(other.forceUpdate, forceUpdate) || other.forceUpdate == forceUpdate));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,minVersion,forceUpdate);
}

@override
String toString() {
    return 'AppVersionRuleDto(minVersion: $minVersion, forceUpdate: $forceUpdate)';
}


}

/// @nodoc
abstract mixin class _$AppVersionRuleDtoCopyWith<$Res> implements $AppVersionRuleDtoCopyWith<$Res> {
  factory _$AppVersionRuleDtoCopyWith(_AppVersionRuleDto value, $Res Function(_AppVersionRuleDto) _then) = __$AppVersionRuleDtoCopyWithImpl;
@override @useResult
$Res call({
@JsonKey(name: 'min_version') String? minVersion,@JsonKey(name: 'force_update') bool forceUpdate
});




}
/// @nodoc
class __$AppVersionRuleDtoCopyWithImpl<$Res>
    implements _$AppVersionRuleDtoCopyWith<$Res> {
  __$AppVersionRuleDtoCopyWithImpl(this._self, this._then);

  final _AppVersionRuleDto _self;
  final $Res Function(_AppVersionRuleDto) _then;

/// Create a copy of AppVersionRuleDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? minVersion = freezed,Object? forceUpdate = null,}) {
  return _then(_AppVersionRuleDto(
minVersion: freezed == minVersion ? _self.minVersion : minVersion // ignore: cast_nullable_to_non_nullable
as String?,forceUpdate: null == forceUpdate ? _self.forceUpdate : forceUpdate // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}


/// @nodoc
mixin _$PolicyVersionDto {

 String get id; int get version;@JsonKey(name: 'published_at') String? get publishedAt;
/// Create a copy of PolicyVersionDto
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$PolicyVersionDtoCopyWith<PolicyVersionDto> get copyWith => _$PolicyVersionDtoCopyWithImpl<PolicyVersionDto>(this as PolicyVersionDto, _$identity);

  /// Serializes this PolicyVersionDto to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  final _this = this as PolicyVersionDto;
  return identical(this, other) || (other.runtimeType == runtimeType&&other is PolicyVersionDto&&(identical(other.id, _this.id) || other.id == _this.id)&&(identical(other.version, _this.version) || other.version == _this.version)&&(identical(other.publishedAt, _this.publishedAt) || other.publishedAt == _this.publishedAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as PolicyVersionDto;
  return Object.hash(runtimeType,_this.id,_this.version,_this.publishedAt);
}

@override
String toString() {
  final _this = this as PolicyVersionDto;
  return 'PolicyVersionDto(id: ${_this.id}, version: ${_this.version}, publishedAt: ${_this.publishedAt})';
}


}

/// @nodoc
abstract mixin class $PolicyVersionDtoCopyWith<$Res>  {
  factory $PolicyVersionDtoCopyWith(PolicyVersionDto value, $Res Function(PolicyVersionDto) _then) = _$PolicyVersionDtoCopyWithImpl;
@useResult
$Res call({
 String id, int version,@JsonKey(name: 'published_at') String? publishedAt
});




}
/// @nodoc
class _$PolicyVersionDtoCopyWithImpl<$Res>
    implements $PolicyVersionDtoCopyWith<$Res> {
  _$PolicyVersionDtoCopyWithImpl(this._self, this._then);

  final PolicyVersionDto _self;
  final $Res Function(PolicyVersionDto) _then;

/// Create a copy of PolicyVersionDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? version = null,Object? publishedAt = freezed,}) {
  return _then(PolicyVersionDto(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,version: null == version ? _self.version : version // ignore: cast_nullable_to_non_nullable
as int,publishedAt: freezed == publishedAt ? _self.publishedAt : publishedAt // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [PolicyVersionDto].
extension PolicyVersionDtoPatterns on PolicyVersionDto {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _PolicyVersionDto value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _PolicyVersionDto() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _PolicyVersionDto value)  $default,){
final _that = this;
switch (_that) {
case _PolicyVersionDto():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _PolicyVersionDto value)?  $default,){
final _that = this;
switch (_that) {
case _PolicyVersionDto() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String id,  int version, @JsonKey(name: 'published_at')  String? publishedAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _PolicyVersionDto() when $default != null:
return $default(_that.id,_that.version,_that.publishedAt);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String id,  int version, @JsonKey(name: 'published_at')  String? publishedAt)  $default,) {final _that = this;
switch (_that) {
case _PolicyVersionDto():
return $default(_that.id,_that.version,_that.publishedAt);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String id,  int version, @JsonKey(name: 'published_at')  String? publishedAt)?  $default,) {final _that = this;
switch (_that) {
case _PolicyVersionDto() when $default != null:
return $default(_that.id,_that.version,_that.publishedAt);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _PolicyVersionDto implements PolicyVersionDto {
  const _PolicyVersionDto({required this.id, required this.version, @JsonKey(name: 'published_at') this.publishedAt});
  factory _PolicyVersionDto.fromJson(Map<String, dynamic> json) => _$PolicyVersionDtoFromJson(json);

@override final  String id;
@override final  int version;
@override@JsonKey(name: 'published_at') final  String? publishedAt;

/// Create a copy of PolicyVersionDto
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$PolicyVersionDtoCopyWith<_PolicyVersionDto> get copyWith => __$PolicyVersionDtoCopyWithImpl<_PolicyVersionDto>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$PolicyVersionDtoToJson(this, );
}

@override
bool operator ==(Object other) {
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _PolicyVersionDto&&(identical(other.id, id) || other.id == id)&&(identical(other.version, version) || other.version == version)&&(identical(other.publishedAt, publishedAt) || other.publishedAt == publishedAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,id,version,publishedAt);
}

@override
String toString() {
    return 'PolicyVersionDto(id: $id, version: $version, publishedAt: $publishedAt)';
}


}

/// @nodoc
abstract mixin class _$PolicyVersionDtoCopyWith<$Res> implements $PolicyVersionDtoCopyWith<$Res> {
  factory _$PolicyVersionDtoCopyWith(_PolicyVersionDto value, $Res Function(_PolicyVersionDto) _then) = __$PolicyVersionDtoCopyWithImpl;
@override @useResult
$Res call({
 String id, int version,@JsonKey(name: 'published_at') String? publishedAt
});




}
/// @nodoc
class __$PolicyVersionDtoCopyWithImpl<$Res>
    implements _$PolicyVersionDtoCopyWith<$Res> {
  __$PolicyVersionDtoCopyWithImpl(this._self, this._then);

  final _PolicyVersionDto _self;
  final $Res Function(_PolicyVersionDto) _then;

/// Create a copy of PolicyVersionDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? version = null,Object? publishedAt = freezed,}) {
  return _then(_PolicyVersionDto(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as String,version: null == version ? _self.version : version // ignore: cast_nullable_to_non_nullable
as int,publishedAt: freezed == publishedAt ? _self.publishedAt : publishedAt // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}

// dart format on
