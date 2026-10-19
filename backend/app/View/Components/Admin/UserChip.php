<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Compact person display: initials avatar, name and optional subtitle (phone, email, role).
 */
class UserChip extends Component
{
    public string $initials;

    public function __construct(public string $name, public ?string $subtitle = null, public ?string $href = null)
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        $this->initials = mb_strtoupper(implode('', array_map(static fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 2))));

        if ($this->initials === '') {
            $this->initials = Str::upper(mb_substr($name, 0, 1)) ?: '?';
        }
    }

    public function render(): View
    {
        return view('components.admin.user-chip');
    }
}
