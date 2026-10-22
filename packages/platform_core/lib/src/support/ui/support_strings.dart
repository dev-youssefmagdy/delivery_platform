/// Texts of the shared chat and support widgets. Each app fills them from its
/// own ARB files (Arabic default, English), so the package has no hard-coded
/// user-visible text.
class ChatStrings {
  const ChatStrings({
    required this.inputHint,
    required this.send,
    required this.attach,
    required this.attachCamera,
    required this.attachGallery,
    required this.removeAttachment,
    required this.empty,
    required this.emptyHint,
    required this.closedTitle,
    required this.closedBody,
    required this.closesSoon,
    required this.sending,
    required this.failed,
    required this.retry,
    required this.discard,
    required this.read,
    required this.delivered,
    required this.loadOlder,
    required this.imageFailed,
    required this.imageLabel,
    required this.removedByRetention,
    required this.tooLong,
    required this.phoneNotice,
  });

  final String inputHint;
  final String send;
  final String attach;
  final String attachCamera;
  final String attachGallery;
  final String removeAttachment;
  final String empty;
  final String emptyHint;
  final String closedTitle;
  final String closedBody;

  /// Shown when the window closes soon; receives the closing time as text.
  final String Function(String time) closesSoon;
  final String sending;
  final String failed;
  final String retry;
  final String discard;
  final String read;
  final String delivered;
  final String loadOlder;
  final String imageFailed;
  final String imageLabel;
  final String removedByRetention;
  final String Function(int max) tooLong;
  final String phoneNotice;
}

class SupportStrings {
  const SupportStrings({
    required this.emptyTitle,
    required this.emptyBody,
    required this.newTicket,
    required this.filterActive,
    required this.filterFinished,
    required this.filterAll,
    required this.loadMore,
    required this.category,
    required this.categoryHint,
    required this.subject,
    required this.subjectHint,
    required this.body,
    required this.bodyHint,
    required this.booking,
    required this.attach,
    required this.attachCamera,
    required this.attachGallery,
    required this.removeAttachment,
    required this.submit,
    required this.required,
    required this.tooShort,
    required this.replyHint,
    required this.replySend,
    required this.replyClosed,
    required this.resolution,
    required this.fromSupport,
    required this.fromMe,
    required this.internalNotice,
    required this.imageFailed,
    required this.attachmentLabel,
    required this.ticketNumber,
    required this.openedOn,
  });

  final String emptyTitle;
  final String emptyBody;
  final String newTicket;
  final String filterActive;
  final String filterFinished;
  final String filterAll;
  final String loadMore;
  final String category;
  final String categoryHint;
  final String subject;
  final String subjectHint;
  final String body;
  final String bodyHint;
  final String Function(String code) booking;
  final String attach;
  final String attachCamera;
  final String attachGallery;
  final String removeAttachment;
  final String submit;
  final String required;
  final String tooShort;
  final String replyHint;
  final String replySend;
  final String replyClosed;
  final String resolution;
  final String fromSupport;
  final String fromMe;
  final String internalNotice;
  final String imageFailed;
  final String attachmentLabel;
  final String Function(String number) ticketNumber;
  final String Function(String date) openedOn;
}
