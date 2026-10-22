<?php

namespace Database\Seeders;

use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use App\Models\PolicyVersion;
use Illuminate\Database\Seeder;

/**
 * A first published restricted-goods policy so parcel bookings work out of the box (P4.1): a parcel request must
 * acknowledge the current version. Created only when no version of the type was ever published, so an operator's own text
 * (published through the policy screens) is never replaced. The wording is a starter for legal review, not final text.
 */
class RestrictedGoodsPolicySeeder extends Seeder
{
    public function run(): void
    {
        if (PolicyVersion::query()->where('type', PolicyType::RestrictedGoods->value)->where('status', ContentStatus::Published->value)->exists()) {
            return;
        }

        PolicyVersion::query()->create([
            'type' => PolicyType::RestrictedGoods,
            'version' => ((int) PolicyVersion::query()->where('type', PolicyType::RestrictedGoods->value)->max('version')) + 1,
            'title' => ['ar' => 'سياسة البضائع المحظورة', 'en' => 'Restricted goods policy'],
            'content' => [
                'ar' => "لا يجوز إرسال: المواد الخطرة أو القابلة للاشتعال، الأسلحة والذخائر، المخدرات والمواد المحظورة قانوناً، النقود والمجوهرات غير المعلنة، الحيوانات الحية، المواد المتحللة أو المسربة.\nأنت مسؤول عن محتوى الطرد وعن صحة القيمة المعلنة. يحق للمندوب رفض أي طرد يشتبه في مخالفته لهذه السياسة، وقد يتم إبلاغ الجهات المختصة.",
                'en' => "You may not send: dangerous or flammable materials, weapons and ammunition, drugs and legally prohibited items, undeclared cash or jewellery, live animals, perishable or leaking items.\nYou are responsible for the contents of the parcel and for the declared value. The courier may refuse any parcel suspected of breaking this policy, and the authorities may be informed.",
            ],
            'status' => ContentStatus::Published,
            'change_note' => 'Starter text',
            'published_at' => now(),
        ]);
    }
}
