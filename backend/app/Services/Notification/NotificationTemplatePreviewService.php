<?php

namespace App\Services\Notification;

use App\Enums\Common\Locale;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;

/**
 * Renders a template in Arabic and English with sample values so an editor sees the final words before saving (and how
 * long an SMS gets). Pure: nothing is stored or sent.
 */
final class NotificationTemplatePreviewService
{
    /** Characters of one unicode SMS segment (Arabic), longer texts are split and cost more. */
    private const SMS_SEGMENT = 70;

    public function __construct(private readonly TemplateRenderer $renderer, private readonly TemplateResolver $resolver) {}

    /**
     * @param array<string, array{title: string|null, body: string}> $content locale => text to render
     * @param array<string, scalar|null> $variables overrides of the sample values
     * @return list<array{locale: string, label: string, direction: string, title: string|null, body: string, unknown: list<string>, length: int, segments: int|null}>
     */
    public function preview(NotificationEvent $event, NotificationChannel $channel, array $content, array $variables = []): array
    {
        $values = $variables + $event->sampleVariables();
        $rows = [];

        foreach (Locale::cases() as $locale) {
            $text = $content[$locale->value] ?? null;

            if ($text === null) {
                continue;
            }

            $localValues = $this->localised($values, $locale->value);
            $body = $this->renderer->render($text['body'], $localValues);
            $rows[] = [
                'locale' => $locale->value,
                'label' => $locale->label(),
                'direction' => $locale->direction(),
                'title' => $text['title'] === null ? null : $this->renderer->render($text['title'], $localValues),
                'body' => $body,
                'unknown' => array_values(array_unique([
                    ...$this->renderer->unknown($text['body'], $event->variables()),
                    ...$this->renderer->unknown((string) $text['title'], $event->variables()),
                ])),
                'length' => mb_strlen($body),
                'segments' => $channel === NotificationChannel::Sms ? max(1, (int) ceil(mb_strlen($body) / self::SMS_SEGMENT)) : null,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, scalar|null> $values
     * @return array<string, scalar|null>
     */
    private function localised(array $values, string $locale): array
    {
        if (isset($values['outcome']) && is_string($values['outcome'])) {
            $values['outcome'] = $this->resolver->variableValue('outcome', $values['outcome'], $locale);
        }

        return $values;
    }
}
