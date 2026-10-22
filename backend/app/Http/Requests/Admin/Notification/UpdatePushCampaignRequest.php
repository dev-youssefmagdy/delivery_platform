<?php

namespace App\Http\Requests\Admin\Notification;

use App\Models\PushCampaign;
use Illuminate\Support\Facades\Gate;

/**
 * Edit a draft or scheduled push campaign (same rules as creating one, plus the optimistic lock).
 */
class UpdatePushCampaignRequest extends StorePushCampaignRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('pushCampaign');

        return $campaign instanceof PushCampaign && Gate::allows('update', $campaign);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['lock_version' => ['nullable', 'integer', 'min:0']];
    }
}
