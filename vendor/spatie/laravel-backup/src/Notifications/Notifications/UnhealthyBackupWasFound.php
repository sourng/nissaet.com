<?php

namespace Spatie\Backup\Notifications\Notifications;

use Spatie\Backup\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Spatie\Backup\Tasks\Monitor\HealthCheckFailure;
use Illuminate\Notifications\Messages\SlackAttachment;
use Spatie\Backup\Events\UnhealthyBackupWasFound as UnhealthyBackupWasFoundEvent;

class UnhealthyBackupWasFound extends BaseNotification
{
    /** @var \Spatie\Backup\Events\UnhealthyBackupWasFound */
    protected $event;

    public function toMail(): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->error()
            ->subject(trans('backup::notifications.unhealthy_backup_found_subject', ['application_name' => $this->applicationName()]))
            ->line(trans('backup::notifications.unhealthy_backup_found_body', ['application_name' => $this->applicationName(), 'disk_name' => $this->diskName()]))
            ->line($this->problemDescription());

        $this->backupDestinationProperties()->each(function ($value, $name) use ($mailMessage) {
            $mailMessage->line("{$name}: $value");
        });

        if ($this->failure()->wasUnexpected()) {
            $mailMessage
                ->line('Health check: '.$this->failure()->healthCheck()->name())
                ->line(trans('backup::notifications.exception_message', ['message' => $this->failure()->exception()->getMessage()]))
                ->line(trans('backup::notifications.exception_trace', ['trace' => $this->failure()->exception()->getTraceAsString()]));
        }

        return $mailMessage;
    }

    public function toSlack(): SlackMessage
    {
        $slackMessage = (new SlackMessage)
            ->error()
            ->from(config('backup.notifications.slack.username'), config('backup.notifications.slack.icon'))
            ->to(config('backup.notifications.slack.channel'))
            ->content(trans('backup::notifications.unhealthy_backup_found_subject_title', ['application_name' => $this->applicationName(), 'problem' => $this->problemDescription()]))
            ->attachment(function (SlackAttachment $attachment) {
                $attachment->fields($this->backupDestinationProperties()->toArray());
            });

        if ($this->failure()->wasUnexpected()) {
            $slackMessage
                ->attachment(function (SlackAttachment $attachment) {
                    $attachment
                        ->title('Health check')
                        ->content($this->failure()->healthCheck()->name());
                })
                ->attachment(function (SlackAttachment $attachment) {
                    $attachment
                        ->title(trans('backup::notifications.exception_message_title'))
                        ->content($this->failure()->exception()->getMessage());
                })
                ->attachment(function (SlackAttachment $attachment) {
                    $attachment
                        ->title(trans('backup::notifications.exception_trace_title'))
                        ->content($this->failure()->exception()->getTraceAsString());
                });
        }

        return $slackMessage;
    }

    protected function problemDescription(): string
    {
        if ($this->failure()->wasUnexpected()) {
            return trans('backup::notifications.unhealthy_backup_found_unknown');
        }

        return $this->failure()->exception()->getMessage();
    }

    protected function failure(): HealthCheckFailure
    {
        return $this->event->backupDestinationStatus->getHealthCheckFailure();
    }

    public function setEvent(UnhealthyBackupWasFoundEvent $event)
    {
        $this->event = $event;

        return $this;
    }
}
