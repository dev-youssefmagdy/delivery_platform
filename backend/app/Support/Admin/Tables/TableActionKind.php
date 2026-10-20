<?php

namespace App\Support\Admin\Tables;

/**
 * How a row/page action is triggered in the browser.
 */
enum TableActionKind: string
{
    /** Plain navigation link. */
    case Link = 'link';

    /** Loads `url` (HTML) into the global modal. */
    case Modal = 'modal';

    /** Loads `url` (HTML) into the global drawer (quick view). */
    case Drawer = 'drawer';

    /** Asks `url` (POST, permissioned, audited when served) for a fresh signed file URL and opens the file in a new tab. */
    case Document = 'document';

    /** Opens the confirm dialog, then sends `method` to `url` via AJAX (optional mandatory reason). */
    case Confirm = 'confirm';
}
