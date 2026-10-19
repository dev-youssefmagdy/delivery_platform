<?php

namespace App\View\Components\Admin;

use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Status chip. Accepts any enum implementing HasLabel&HasColor (`:value="$model->status"`), or an explicit
 * label + colour. Colour is never the only signal: the label is always rendered (SRS §18.3).
 */
class Badge extends Component
{
    public string $text;

    public string $tone;

    public function __construct(
        public (HasLabel&HasColor)|null $value = null,
        ?string $label = null,
        BadgeColor|string|null $color = null,
        public bool $dot = true,
    ) {
        $this->text = $label ?? $value?->label() ?? '—';

        $resolved = $color instanceof BadgeColor ? $color : BadgeColor::tryFrom((string) $color);
        $this->tone = ($resolved ?? $value?->color() ?? BadgeColor::Neutral)->value;
    }

    public function render(): View
    {
        return view('components.admin.badge');
    }
}
