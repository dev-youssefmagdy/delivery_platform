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

@JsonKey(name: 'default_locale') String? get defaultLocale; List<String> get locales; CurrencyDto? get currency;@JsonKey(name: 'digits_preference') String? get digitsPreference; bool get maintenance;@JsonKey(name: 'min_version') String? get minVersion;
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
  return identical(this, other) || (other.runtimeType == runtimeType&&other is AppConfigDto&&(identical(other.defaultLocale, _this.defaultLocale) || other.defaultLocale == _this.defaultLocale)&&const DeepCollectionEquality().equals(other.locales, _this.locales)&&(identical(other.currency, _this.currency) || other.currency == _this.currency)&&(identical(other.digitsPreference, _this.digitsPreference) || other.digitsPreference == _this.digitsPreference)&&(identical(other.maintenance, _this.maintenance) || other.maintenance == _this.maintenance)&&(identical(other.minVersion, _this.minVersion) || other.minVersion == _this.minVersion));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as AppConfigDto;
  return Object.hash(runtimeType,_this.defaultLocale,const DeepCollectionEquality().hash(_this.locales),_this.currency,_this.digitsPreference,_this.maintenance,_this.minVersion);
}

@override
String toString() {
  final _this = this as AppConfigDto;
  return 'AppConfigDto(defaultLocale: ${_this.defaultLocale}, locales: ${_this.locales}, currency: ${_this.currency}, digitsPreference: ${_this.digitsPreference}, maintenance: ${_this.maintenance}, minVersion: ${_this.minVersion})';
}


}

/// @nodoc
abstract mixin class $AppConfigDtoCopyWith<$Res>  {
  factory $AppConfigDtoCopyWith(AppConfigDto value, $Res Function(AppConfigDto) _then) = _$AppConfigDtoCopyWithImpl;
@useResult
$Res call({
@JsonKey(name: 'default_locale') String? defaultLocale, List<String> locales, CurrencyDto? currency,@JsonKey(name: 'digits_preference') String? digitsPreference, bool maintenance,@JsonKey(name: 'min_version') String? minVersion
});


$CurrencyDtoCopyWith<$Res>? get currency;

}
/// @nodoc
class _$AppConfigDtoCopyWithImpl<$Res>
    implements $AppConfigDtoCopyWith<$Res> {
  _$AppConfigDtoCopyWithImpl(this._self, this._then);

  final AppConfigDto _self;
  final $Res Function(AppConfigDto) _then;

/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? defaultLocale = freezed,Object? locales = null,Object? currency = freezed,Object? digitsPreference = freezed,Object? maintenance = null,Object? minVersion = freezed,}) {
  return _then(AppConfigDto(
defaultLocale: freezed == defaultLocale ? _self.defaultLocale : defaultLocale // ignore: cast_nullable_to_non_nullable
as String?,locales: null == locales ? _self.locales : locales // ignore: cast_nullable_to_non_nullable
as List<String>,currency: freezed == currency ? _self.currency : currency // ignore: cast_nullable_to_non_nullable
as CurrencyDto?,digitsPreference: freezed == digitsPreference ? _self.digitsPreference : digitsPreference // ignore: cast_nullable_to_non_nullable
as String?,maintenance: null == maintenance ? _self.maintenance : maintenance // ignore: cast_nullable_to_non_nullable
as bool,minVersion: freezed == minVersion ? _self.minVersion : minVersion // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}
/// Create a copy of AppConfigDto
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function(@JsonKey(name: 'default_locale')  String? defaultLocale,  List<String> locales,  CurrencyDto? currency, @JsonKey(name: 'digits_preference')  String? digitsPreference,  bool maintenance, @JsonKey(name: 'min_version')  String? minVersion)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _AppConfigDto() when $default != null:
return $default(_that.defaultLocale,_that.locales,_that.currency,_that.digitsPreference,_that.maintenance,_that.minVersion);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function(@JsonKey(name: 'default_locale')  String? defaultLocale,  List<String> locales,  CurrencyDto? currency, @JsonKey(name: 'digits_preference')  String? digitsPreference,  bool maintenance, @JsonKey(name: 'min_version')  String? minVersion)  $default,) {final _that = this;
switch (_that) {
case _AppConfigDto():
return $default(_that.defaultLocale,_that.locales,_that.currency,_that.digitsPreference,_that.maintenance,_that.minVersion);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function(@JsonKey(name: 'default_locale')  String? defaultLocale,  List<String> locales,  CurrencyDto? currency, @JsonKey(name: 'digits_preference')  String? digitsPreference,  bool maintenance, @JsonKey(name: 'min_version')  String? minVersion)?  $default,) {final _that = this;
switch (_that) {
case _AppConfigDto() when $default != null:
return $default(_that.defaultLocale,_that.locales,_that.currency,_that.digitsPreference,_that.maintenance,_that.minVersion);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _AppConfigDto implements AppConfigDto {
  const _AppConfigDto({@JsonKey(name: 'default_locale') this.defaultLocale,  List<String> locales = const <String>[], this.currency, @JsonKey(name: 'digits_preference') this.digitsPreference, this.maintenance = false, @JsonKey(name: 'min_version') this.minVersion}): _locales = locales;
  factory _AppConfigDto.fromJson(Map<String, dynamic> json) => _$AppConfigDtoFromJson(json);

@override@JsonKey(name: 'default_locale') final  String? defaultLocale;
 final  List<String> _locales;
@override@JsonKey() List<String> get locales {
  if (_locales is EqualUnmodifiableListView) return _locales;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_locales);
}

@override final  CurrencyDto? currency;
@override@JsonKey(name: 'digits_preference') final  String? digitsPreference;
@override@JsonKey() final  bool maintenance;
@override@JsonKey(name: 'min_version') final  String? minVersion;

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
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _AppConfigDto&&(identical(other.defaultLocale, defaultLocale) || other.defaultLocale == defaultLocale)&&const DeepCollectionEquality().equals(other.locales, _locales)&&(identical(other.currency, currency) || other.currency == currency)&&(identical(other.digitsPreference, digitsPreference) || other.digitsPreference == digitsPreference)&&(identical(other.maintenance, maintenance) || other.maintenance == maintenance)&&(identical(other.minVersion, minVersion) || other.minVersion == minVersion));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,defaultLocale,const DeepCollectionEquality().hash(_locales),currency,digitsPreference,maintenance,minVersion);
}

@override
String toString() {
    return 'AppConfigDto(defaultLocale: $defaultLocale, locales: $locales, currency: $currency, digitsPreference: $digitsPreference, maintenance: $maintenance, minVersion: $minVersion)';
}


}

/// @nodoc
abstract mixin class _$AppConfigDtoCopyWith<$Res> implements $AppConfigDtoCopyWith<$Res> {
  factory _$AppConfigDtoCopyWith(_AppConfigDto value, $Res Function(_AppConfigDto) _then) = __$AppConfigDtoCopyWithImpl;
@override @useResult
$Res call({
@JsonKey(name: 'default_locale') String? defaultLocale, List<String> locales, CurrencyDto? currency,@JsonKey(name: 'digits_preference') String? digitsPreference, bool maintenance,@JsonKey(name: 'min_version') String? minVersion
});


@override $CurrencyDtoCopyWith<$Res>? get currency;

}
/// @nodoc
class __$AppConfigDtoCopyWithImpl<$Res>
    implements _$AppConfigDtoCopyWith<$Res> {
  __$AppConfigDtoCopyWithImpl(this._self, this._then);

  final _AppConfigDto _self;
  final $Res Function(_AppConfigDto) _then;

/// Create a copy of AppConfigDto
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? defaultLocale = freezed,Object? locales = null,Object? currency = freezed,Object? digitsPreference = freezed,Object? maintenance = null,Object? minVersion = freezed,}) {
  return _then(_AppConfigDto(
defaultLocale: freezed == defaultLocale ? _self.defaultLocale : defaultLocale // ignore: cast_nullable_to_non_nullable
as String?,locales: null == locales ? _self._locales : locales // ignore: cast_nullable_to_non_nullable
as List<String>,currency: freezed == currency ? _self.currency : currency // ignore: cast_nullable_to_non_nullable
as CurrencyDto?,digitsPreference: freezed == digitsPreference ? _self.digitsPreference : digitsPreference // ignore: cast_nullable_to_non_nullable
as String?,maintenance: null == maintenance ? _self.maintenance : maintenance // ignore: cast_nullable_to_non_nullable
as bool,minVersion: freezed == minVersion ? _self.minVersion : minVersion // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

/// Create a copy of AppConfigDto
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
}
}


/// @nodoc
mixin _$CurrencyDto {

 String get code; int get exponent;
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
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CurrencyDto&&(identical(other.code, _this.code) || other.code == _this.code)&&(identical(other.exponent, _this.exponent) || other.exponent == _this.exponent));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
  final _this = this as CurrencyDto;
  return Object.hash(runtimeType,_this.code,_this.exponent);
}

@override
String toString() {
  final _this = this as CurrencyDto;
  return 'CurrencyDto(code: ${_this.code}, exponent: ${_this.exponent})';
}


}

/// @nodoc
abstract mixin class $CurrencyDtoCopyWith<$Res>  {
  factory $CurrencyDtoCopyWith(CurrencyDto value, $Res Function(CurrencyDto) _then) = _$CurrencyDtoCopyWithImpl;
@useResult
$Res call({
 String code, int exponent
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
@pragma('vm:prefer-inline') @override $Res call({Object? code = null,Object? exponent = null,}) {
  return _then(CurrencyDto(
code: null == code ? _self.code : code // ignore: cast_nullable_to_non_nullable
as String,exponent: null == exponent ? _self.exponent : exponent // ignore: cast_nullable_to_non_nullable
as int,
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String code,  int exponent)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CurrencyDto() when $default != null:
return $default(_that.code,_that.exponent);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String code,  int exponent)  $default,) {final _that = this;
switch (_that) {
case _CurrencyDto():
return $default(_that.code,_that.exponent);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String code,  int exponent)?  $default,) {final _that = this;
switch (_that) {
case _CurrencyDto() when $default != null:
return $default(_that.code,_that.exponent);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _CurrencyDto implements CurrencyDto {
  const _CurrencyDto({required this.code, this.exponent = 2});
  factory _CurrencyDto.fromJson(Map<String, dynamic> json) => _$CurrencyDtoFromJson(json);

@override final  String code;
@override@JsonKey() final  int exponent;

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
    return identical(this, other) || (other.runtimeType == runtimeType&&other is _CurrencyDto&&(identical(other.code, code) || other.code == code)&&(identical(other.exponent, exponent) || other.exponent == exponent));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode {
    return Object.hash(runtimeType,code,exponent);
}

@override
String toString() {
    return 'CurrencyDto(code: $code, exponent: $exponent)';
}


}

/// @nodoc
abstract mixin class _$CurrencyDtoCopyWith<$Res> implements $CurrencyDtoCopyWith<$Res> {
  factory _$CurrencyDtoCopyWith(_CurrencyDto value, $Res Function(_CurrencyDto) _then) = __$CurrencyDtoCopyWithImpl;
@override @useResult
$Res call({
 String code, int exponent
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
@override @pragma('vm:prefer-inline') $Res call({Object? code = null,Object? exponent = null,}) {
  return _then(_CurrencyDto(
code: null == code ? _self.code : code // ignore: cast_nullable_to_non_nullable
as String,exponent: null == exponent ? _self.exponent : exponent // ignore: cast_nullable_to_non_nullable
as int,
  ));
}


}

// dart format on
