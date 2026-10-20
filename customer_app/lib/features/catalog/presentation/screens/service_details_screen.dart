import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/catalog_providers.dart';
import '../widgets/form_strings.dart';

/// The selected service's dynamic form (`DynamicFormRenderer`): the same
/// schema the server validates for `service_details`. Saved answers are kept
/// for the booking flow (P2.6).
class ServiceDetailsScreen extends ConsumerStatefulWidget {
  const ServiceDetailsScreen({super.key});

  @override
  ConsumerState<ServiceDetailsScreen> createState() =>
      _ServiceDetailsScreenState();
}

class _ServiceDetailsScreenState extends ConsumerState<ServiceDetailsScreen> {
  DynamicFormController? _controller;
  int? _categoryId;

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  DynamicFormController _controllerFor(ServiceCategoryInfo category) {
    if (_categoryId != category.id) {
      _controller?.dispose();
      _categoryId = category.id;
      _controller = DynamicFormController(
        fields: category.formSchema.fields,
        initialValues:
            ref.read(serviceDetailsProvider)[category.id] ?? const {},
      );
    }
    return _controller!;
  }

  void _save(ServiceCategoryInfo category, DynamicFormController controller) {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    if (!controller.validate()) {
      messenger.showSnackBar(
        SnackBar(content: Text(l10n.catalogDetailsFixErrors)),
      );
      return;
    }
    ref
        .read(serviceDetailsProvider.notifier)
        .save(category.id, controller.payload);
    context.pop();
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(l10n.catalogDetailsSaved)));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final category = ref.watch(selectedCategoryProvider);
    final language = Localizations.localeOf(context).languageCode;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.catalogDetailsTitle)),
      body: category == null || category.formSchema.fields.isEmpty
          ? EmptyState(title: l10n.catalogDetailsNone)
          : _body(category, language, l10n),
    );
  }

  Widget _body(
    ServiceCategoryInfo category,
    String language,
    AppLocalizations l10n,
  ) {
    final controller = _controllerFor(category);
    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(
          category.name.resolve(language),
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(l10n.catalogDetailsIntro),
        const SizedBox(height: AppSpacing.md),
        DynamicFormRenderer(
          controller: controller,
          languageCode: language,
          strings: dynamicFormStrings(l10n),
          photoHandler: ImagePickerPhotoHandler(
            cameraLabel: l10n.formPhotoCamera,
            galleryLabel: l10n.formPhotoGallery,
          ),
        ),
        AppButton(
          key: const Key('save-service-details'),
          label: l10n.catalogDetailsSave,
          onPressed: () => _save(category, controller),
        ),
      ],
    );
  }
}
