<?php

namespace App\Console\Commands;

use App\Models\Insurance;
use App\Services\InsuranceService;
use App\Telegram\AllowedChats;
use App\Telegram\FormLinks;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Telegram\Bot\Api;

#[Signature('insurance:notify-expiring')]
#[Description('Send a Telegram summary of overdue and soon-to-expire insurance policies.')]
class SendInsuranceExpiryNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(InsuranceService $insurances, Api $telegram): int
    {
        $groups = $insurances->expiringGroups();

        if ($this->isEmpty($groups)) {
            return self::SUCCESS;
        }

        $message = $this->formatMessage($groups);

        foreach (AllowedChats::ids() as $chatId) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[['text' => '🔔 View in App', 'web_app' => ['url' => FormLinks::launch('notifications')]]]],
                ]),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{overdue: Collection<int, Insurance>, buckets: array<int, Collection<int, Insurance>>}  $groups
     */
    private function isEmpty(array $groups): bool
    {
        return $groups['overdue']->isEmpty()
            && collect($groups['buckets'])->every(fn (Collection $policies): bool => $policies->isEmpty());
    }

    /**
     * @param  array{overdue: Collection<int, Insurance>, buckets: array<int, Collection<int, Insurance>>}  $groups
     */
    private function formatMessage(array $groups): string
    {
        $sections = [];

        if ($groups['overdue']->isNotEmpty()) {
            $sections[] = "Already expired:\n".$this->formatList($groups['overdue']);
        }

        foreach ($groups['buckets'] as $days => $policies) {
            if ($policies->isNotEmpty()) {
                $sections[] = "Expiring in {$days} days:\n".$this->formatList($policies);
            }
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param  Collection<int, Insurance>  $policies
     */
    private function formatList(Collection $policies): string
    {
        return $policies
            ->map(fn (Insurance $insurance): string => sprintf(
                '- %s (%s) expires %s',
                $insurance->policy_no,
                $insurance->insured_name,
                $insurance->expiry_date->format('Y-m-d'),
            ))
            ->implode("\n");
    }
}
