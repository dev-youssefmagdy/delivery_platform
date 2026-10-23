/// Texts of the shared safety widgets. Each app fills them from its own ARB files (Arabic default, English), so the package has
/// no hard-coded user-visible text.
class SafetyStrings {
  const SafetyStrings({
    required this.title,
    required this.sosHoldLabel,
    required this.sosHoldHint,
    required this.sosHolding,
    required this.sosSending,
    required this.sosSentTitle,
    required this.sosFailedTitle,
    required this.sosFailedBody,
    required this.sosRetry,
    required this.sosNoJob,
    required this.emergencyNumbersTitle,
    required this.emergencyNumberName,
    required this.callNumber,
    required this.noDispatchNotice,
    required this.whatWeDid,
    required this.reportIncident,
    required this.shareTrip,
    required this.manageContacts,
    required this.callSupport,
    required this.close,
    required this.done,
    required this.retry,
    required this.cancel,
    required this.errorGeneric,
    required this.errorOffline,
    required this.errorText,
    required this.categoryName,
    required this.reportTitle,
    required this.reportCategory,
    required this.reportNarrative,
    required this.reportNarrativeHint,
    required this.reportTooShort,
    required this.reportCallback,
    required this.reportSubmit,
    required this.reportSentTitle,
    required this.reportUrgentNote,
    required this.incidentsTitle,
    required this.incidentsEmpty,
    required this.incidentStatus,
    required this.incidentOutcome,
    required this.incidentTimeline,
    required this.incidentContacts,
    required this.incidentFiles,
    required this.incidentAddUpdate,
    required this.incidentUpdateHint,
    required this.incidentSendUpdate,
    required this.incidentAddFile,
    required this.incidentResolvedNote,
    required this.incidentNumber,
    required this.eventActor,
    required this.contactsTitle,
    required this.contactsIntro,
    required this.contactsEmpty,
    required this.contactsAdd,
    required this.contactsLimit,
    required this.contactName,
    required this.contactPhone,
    required this.contactPhoneHint,
    required this.contactRelationship,
    required this.relationshipName,
    required this.consentTitle,
    required this.consentConfirm,
    required this.consentSos,
    required this.consentTrip,
    required this.consentLocation,
    required this.contactSave,
    required this.contactDelete,
    required this.contactDeleteConfirm,
    required this.contactRequired,
    required this.contactConsentRequired,
    required this.shareTitle,
    required this.shareBody,
    required this.shareNoContacts,
    required this.shareSend,
    required this.shareResult,
    required this.shareCopy,
    required this.shareCopied,
    required this.shareStop,
    required this.shareStopped,
    required this.shareExpires,
  });

  final String title;
  final String sosHoldLabel;

  /// "Hold for N seconds".
  final String Function(int seconds) sosHoldHint;
  final String sosHolding;
  final String sosSending;
  final String sosSentTitle;
  final String sosFailedTitle;
  final String sosFailedBody;
  final String sosRetry;
  final String sosNoJob;
  final String emergencyNumbersTitle;

  /// Name of a service key (`police`, `ambulance`, `fire`, `tourist_police`).
  final String Function(String key) emergencyNumberName;
  final String Function(String name, String number) callNumber;
  final String noDispatchNotice;
  final String whatWeDid;
  final String reportIncident;
  final String shareTrip;
  final String manageContacts;
  final String callSupport;
  final String close;
  final String done;
  final String retry;
  final String cancel;
  final String errorGeneric;
  final String errorOffline;

  /// Text for a stable error code, or null.
  final String? Function(Object error) errorText;
  final String Function(String category) categoryName;
  final String reportTitle;
  final String reportCategory;
  final String reportNarrative;
  final String reportNarrativeHint;
  final String reportTooShort;
  final String reportCallback;
  final String reportSubmit;
  final String reportSentTitle;
  final String reportUrgentNote;
  final String incidentsTitle;
  final String incidentsEmpty;
  final String Function(String status, String fallback) incidentStatus;
  final String Function(String outcome) incidentOutcome;
  final String incidentTimeline;
  final String incidentContacts;
  final String incidentFiles;
  final String incidentAddUpdate;
  final String incidentUpdateHint;
  final String incidentSendUpdate;
  final String incidentAddFile;
  final String incidentResolvedNote;
  final String Function(String number) incidentNumber;
  final String Function(String actor) eventActor;
  final String contactsTitle;
  final String contactsIntro;
  final String contactsEmpty;
  final String contactsAdd;
  final String Function(int max) contactsLimit;
  final String contactName;
  final String contactPhone;
  final String contactPhoneHint;
  final String contactRelationship;
  final String Function(String key) relationshipName;
  final String consentTitle;
  final String consentConfirm;
  final String consentSos;
  final String consentTrip;
  final String consentLocation;
  final String contactSave;
  final String contactDelete;
  final String contactDeleteConfirm;
  final String contactRequired;
  final String contactConsentRequired;
  final String shareTitle;
  final String shareBody;
  final String shareNoContacts;
  final String shareSend;

  /// "Sent to N of M contacts".
  final String Function(int sent, int total) shareResult;
  final String shareCopy;
  final String shareCopied;
  final String shareStop;
  final String shareStopped;
  final String Function(String time) shareExpires;
}
